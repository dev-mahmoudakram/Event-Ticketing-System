<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AgendaItem;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaItemCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_agenda_item(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.agenda-items.store', $event), [
            'day_date' => '2026-08-15', 'start_time' => '09:00', 'end_time' => '10:00',
            'title_ar' => 'الافتتاح', 'title_en' => 'Opening', 'session_type_id' => $event->sessionTypes()->where('name_en', 'Keynote')->value('id'), 'sort_order' => 0,
        ]);

        $response->assertRedirect(route('admin.events.agenda-items.index', $event));
        $this->assertDatabaseHas('agenda_items', ['event_id' => $event->id, 'title_en' => 'Opening', 'session_type_id' => $event->sessionTypes()->where('name_en', 'Keynote')->value('id')]);
    }

    public function test_creating_an_agenda_item_requires_end_time_after_start_time(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.agenda-items.store', $event), [
            'day_date' => '2026-08-15', 'start_time' => '10:00', 'end_time' => '09:00',
            'title_ar' => 'الافتتاح', 'title_en' => 'Opening', 'session_type_id' => $event->sessionTypes()->where('name_en', 'Keynote')->value('id'),
        ]);

        $response->assertSessionHasErrors('end_time');
    }

    public function test_admin_can_delete_an_agenda_item(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $item = AgendaItem::factory()->for($event)->create();

        $response = $this->actingAs($admin)->delete(route('admin.events.agenda-items.destroy', [$event, $item]));

        $response->assertRedirect(route('admin.events.agenda-items.index', $event));
        $this->assertDatabaseMissing('agenda_items', ['id' => $item->id]);
    }

    public function test_admin_can_view_the_index_page_with_records(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        AgendaItem::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.agenda-items.index', $event));

        $response->assertOk();
    }

    public function test_admin_can_view_the_edit_page(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $item = AgendaItem::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.agenda-items.edit', [$event, $item]));

        $response->assertOk();
    }
}
