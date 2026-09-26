<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\Location;
use App\Models\SessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleListsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_event_gets_the_five_default_session_types(): void
    {
        $event = Event::factory()->create();

        $this->assertSame(['Keynote', 'Session', 'Workshop', 'Break', 'Panel'], $event->sessionTypes()->pluck('name_en')->all());
        $this->assertTrue($event->sessionTypes()->where('name_en', 'Break')->sole()->is_break);
        $this->assertSame(1, $event->sessionTypes()->where('is_break', true)->count());
    }

    public function test_an_admin_adds_edits_and_deletes_a_session_type(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($admin)->post(route('admin.events.session-types.store', $event), [
            'name_ar' => 'جلسة حوارية', 'name_en' => 'Fireside chat',
        ])->assertRedirect(route('admin.events.session-types.index', $event));
        $type = $event->sessionTypes()->where('name_en', 'Fireside chat')->sole();
        $this->assertSame(5, $type->sort_order);

        $this->actingAs($admin)->put(route('admin.events.session-types.update', [$event, $type]), [
            'name_ar' => 'جلسة حوارية', 'name_en' => 'Fireside', 'is_break' => '1',
        ])->assertRedirect(route('admin.events.session-types.index', $event));
        $this->assertTrue($type->fresh()->is_break);

        $this->actingAs($admin)->delete(route('admin.events.session-types.destroy', [$event, $type]))
            ->assertRedirect(route('admin.events.session-types.index', $event));
        $this->assertModelMissing($type);
    }

    public function test_an_admin_manages_locations(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($admin)->post(route('admin.events.locations.store', $event), [
            'name_ar' => 'المسرح', 'name_en' => 'Main Stage',
        ])->assertRedirect(route('admin.events.locations.index', $event));

        $this->actingAs($admin)->get(route('admin.events.locations.index', ['event' => $event, 'lang' => 'en']))
            ->assertOk()->assertSee('Main Stage');
    }

    public function test_names_are_required_in_both_languages(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.events.locations.store', Event::factory()->create()), ['name_ar' => '', 'name_en' => ''])
            ->assertSessionHasErrors(['name_ar', 'name_en']);
    }

    public function test_lists_reorder(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ids = $event->sessionTypes()->pluck('id')->reverse()->values()->all();

        $this->actingAs($admin)->postJson(route('admin.events.session-types.reorder', $event), ['ids' => $ids])->assertOk();

        $this->assertSame($ids, $event->sessionTypes()->pluck('id')->all());
    }

    public function test_another_events_rows_cannot_be_reached(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $foreign = Location::factory()->create();

        $this->actingAs($admin)->get(route('admin.events.locations.edit', [$event, $foreign]))->assertNotFound();
        $this->actingAs($admin)->delete(route('admin.events.locations.destroy', [$event, $foreign]))->assertNotFound();
        $this->assertModelExists($foreign);

        $foreignType = SessionType::factory()->create();
        $this->actingAs($admin)->postJson(route('admin.events.session-types.reorder', $event), ['ids' => [$foreignType->id]])
            ->assertUnprocessable();
    }
}
