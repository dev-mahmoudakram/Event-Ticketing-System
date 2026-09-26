<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\Location;
use App\Models\Speaker;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkshopCrudTest extends TestCase
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
            'slug' => 'ai-workshop', 'name_ar' => 'ورشة', 'name_en' => 'AI Workshop', 'capacity' => 30,
            'day_date' => '2026-08-15', 'start_time' => '14:00', 'end_time' => '15:30',
        ];
    }

    public function test_admin_creates_a_scheduled_workshop_with_speakers_and_location(): void
    {
        $location = Location::factory()->for($this->event)->create();
        [$a, $b] = Speaker::factory()->for($this->event)->count(2)->create()->all();

        $this->actingAs($this->admin)->post(route('admin.events.workshops.store', $this->event), $this->payload([
            'location_id' => $location->id, 'speaker_ids' => [$b->id, $a->id],
        ]))->assertRedirect(route('admin.events.workshops.index', $this->event));

        $workshop = Workshop::where('slug', 'ai-workshop')->sole();
        $this->assertSame('14:00', $workshop->start_time->format('H:i'));
        $this->assertTrue($workshop->location->is($location));
        $this->assertSame([$b->id, $a->id], $workshop->speakers->pluck('id')->all());
    }

    public function test_a_workshop_needs_a_day_and_times_within_the_event(): void
    {
        $this->actingAs($this->admin)->post(route('admin.events.workshops.store', $this->event), $this->payload([
            'day_date' => '', 'start_time' => '', 'end_time' => '',
        ]))->assertSessionHasErrors(['day_date', 'start_time', 'end_time']);

        $this->actingAs($this->admin)->post(route('admin.events.workshops.store', $this->event), $this->payload([
            'day_date' => '2026-09-01', 'start_time' => '15:00', 'end_time' => '14:00',
        ]))->assertSessionHasErrors(['day_date', 'end_time']);
    }

    public function test_creating_a_workshop_requires_a_unique_slug(): void
    {
        Workshop::factory()->create(['slug' => 'ai-workshop']);

        $this->actingAs($this->admin)->post(route('admin.events.workshops.store', $this->event), $this->payload())
            ->assertSessionHasErrors('slug');
    }

    public function test_speakers_and_location_must_belong_to_this_event(): void
    {
        $this->actingAs($this->admin)->post(route('admin.events.workshops.store', $this->event), $this->payload([
            'location_id' => Location::factory()->create()->id,
            'speaker_ids' => [Speaker::factory()->create()->id],
        ]))->assertSessionHasErrors(['location_id', 'speaker_ids.0']);
    }

    public function test_admin_can_delete_a_workshop(): void
    {
        $workshop = Workshop::factory()->for($this->event)->create();

        $this->actingAs($this->admin)->delete(route('admin.events.workshops.destroy', [$this->event, $workshop]))
            ->assertRedirect(route('admin.events.workshops.index', $this->event));

        $this->assertModelMissing($workshop);
    }

    public function test_the_index_and_edit_pages_load(): void
    {
        $workshop = Workshop::factory()->for($this->event)->create();

        $this->actingAs($this->admin)->get(route('admin.events.workshops.index', $this->event))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.events.workshops.edit', [$this->event, $workshop]))
            ->assertOk()
            ->assertSee('data-speaker-picker', false)
            ->assertSee('name="day_date"', false);
    }
}
