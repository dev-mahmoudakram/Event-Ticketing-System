<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AgendaItem;
use App\Models\Event;
use App\Models\Location;
use App\Models\SessionType;
use App\Models\Speaker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaItemCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->event = Event::factory()->create(['start_date' => '2026-08-15', 'end_date' => '2026-08-16']);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'day_date' => '2026-08-15', 'start_time' => '09:00', 'end_time' => '10:00',
            'title_ar' => 'الافتتاح', 'title_en' => 'Opening',
            'session_type_id' => $this->event->sessionTypes()->where('name_en', 'Keynote')->value('id'),
        ];
    }

    public function test_admin_creates_a_session_with_several_speakers_in_order(): void
    {
        $location = Location::factory()->for($this->event)->create();
        [$a, $b] = Speaker::factory()->for($this->event)->count(2)->create()->all();

        $this->actingAs($this->admin)->post(route('admin.events.agenda-items.store', $this->event), $this->payload([
            'location_id' => $location->id,
            'description_en' => '<p>Long <strong>details</strong></p>',
            'speaker_ids' => [$b->id, $a->id],
        ]))->assertRedirect(route('admin.events.agenda-items.index', $this->event));

        $item = AgendaItem::where('title_en', 'Opening')->sole();
        $this->assertSame('Keynote', $item->sessionType->name_en);
        $this->assertTrue($item->location->is($location));
        $this->assertSame([$b->id, $a->id], $item->speakers->pluck('id')->all());
        $this->assertStringContainsString('<strong>details</strong>', $item->description_en);
    }

    public function test_editing_can_reorder_and_remove_speakers(): void
    {
        [$a, $b, $c] = Speaker::factory()->for($this->event)->count(3)->create()->all();
        $item = AgendaItem::factory()->for($this->event)->create(['day_date' => '2026-08-15']);
        $item->syncSpeakersInOrder([$a->id, $b->id, $c->id]);

        $this->actingAs($this->admin)->put(route('admin.events.agenda-items.update', [$this->event, $item]), $this->payload([
            'speaker_ids' => [$c->id, $a->id],
        ]))->assertRedirect(route('admin.events.agenda-items.index', $this->event));

        $this->assertSame([$c->id, $a->id], $item->fresh()->speakers->pluck('id')->all());

        $this->actingAs($this->admin)->put(route('admin.events.agenda-items.update', [$this->event, $item]), $this->payload());
        $this->assertCount(0, $item->fresh()->speakers);
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $this->actingAs($this->admin)->post(route('admin.events.agenda-items.store', $this->event), $this->payload(['start_time' => '10:00', 'end_time' => '09:00']))
            ->assertSessionHasErrors('end_time');
    }

    public function test_the_day_must_fall_within_the_event(): void
    {
        $this->actingAs($this->admin)->post(route('admin.events.agenda-items.store', $this->event), $this->payload(['day_date' => '2026-08-20']))
            ->assertSessionHasErrors('day_date');
    }

    public function test_type_location_and_speakers_must_belong_to_this_event(): void
    {
        $this->actingAs($this->admin)->post(route('admin.events.agenda-items.store', $this->event), $this->payload([
            'session_type_id' => SessionType::factory()->create()->id,
            'location_id' => Location::factory()->create()->id,
            'speaker_ids' => [Speaker::factory()->create()->id],
        ]))->assertSessionHasErrors(['session_type_id', 'location_id', 'speaker_ids.0']);

        $this->assertDatabaseCount('agenda_items', 0);
    }

    public function test_a_speaker_cannot_be_listed_twice(): void
    {
        $speaker = Speaker::factory()->for($this->event)->create();

        $this->actingAs($this->admin)->post(route('admin.events.agenda-items.store', $this->event), $this->payload([
            'speaker_ids' => [$speaker->id, $speaker->id],
        ]))->assertSessionHasErrors('speaker_ids.0');
    }

    public function test_admin_can_delete_an_agenda_item(): void
    {
        $item = AgendaItem::factory()->for($this->event)->create();

        $this->actingAs($this->admin)->delete(route('admin.events.agenda-items.destroy', [$this->event, $item]))
            ->assertRedirect(route('admin.events.agenda-items.index', $this->event));

        $this->assertModelMissing($item);
    }

    public function test_the_list_shows_time_range_type_location_and_speaker_count(): void
    {
        $location = Location::factory()->for($this->event)->create(['name_en' => 'Main Stage']);
        $item = AgendaItem::factory()->for($this->event)->ofType('Panel')->create([
            'start_time' => '11:15', 'end_time' => '12:00', 'location_id' => $location->id,
        ]);
        $item->syncSpeakersInOrder(Speaker::factory()->for($this->event)->count(3)->create()->pluck('id')->all());

        $this->actingAs($this->admin)->get(route('admin.events.agenda-items.index', ['event' => $this->event, 'lang' => 'en']))
            ->assertOk()
            ->assertSee('11:15–12:00')
            ->assertSee('Panel')
            ->assertSee('Main Stage')
            ->assertSee(route('admin.events.workshops.index', $this->event), false);
    }

    public function test_the_form_shows_the_speaker_picker_and_editor(): void
    {
        $item = AgendaItem::factory()->for($this->event)->create();

        $this->actingAs($this->admin)->get(route('admin.events.agenda-items.edit', [$this->event, $item]))
            ->assertOk()
            ->assertSee('data-speaker-picker', false)
            ->assertSee('name="description_en"', false)
            ->assertSee('name="session_type_id"', false)
            ->assertSee('name="location_id"', false);
    }

    public function test_removed_speakers_stay_removed_after_a_failed_save(): void
    {
        $speaker = Speaker::factory()->for($this->event)->create();
        $item = AgendaItem::factory()->for($this->event)->create(['day_date' => '2026-08-15']);
        $item->syncSpeakersInOrder([$speaker->id]);

        $this->actingAs($this->admin)
            ->from(route('admin.events.agenda-items.edit', [$this->event, $item]))
            ->put(route('admin.events.agenda-items.update', [$this->event, $item]), $this->payload(['title_en' => '']))
            ->assertSessionHasErrors('title_en');

        $this->actingAs($this->admin)->get(route('admin.events.agenda-items.edit', [$this->event, $item]))
            ->assertOk()
            ->assertSee('chosen: []', false);
    }
}
