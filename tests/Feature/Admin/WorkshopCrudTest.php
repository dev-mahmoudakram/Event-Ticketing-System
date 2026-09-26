<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\Speaker;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkshopCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_workshop_with_a_speaker(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $speaker = Speaker::factory()->for($event)->create();

        $response = $this->actingAs($admin)->post(route('admin.events.workshops.store', $event), [
            'slug' => 'ai-workshop',
            'name_ar' => 'ورشة', 'name_en' => 'AI Workshop',
            'capacity' => 30, 'sort_order' => 0,
        ]);

        $response->assertRedirect(route('admin.events.workshops.index', $event));
        $this->assertDatabaseHas('workshops', ['event_id' => $event->id, 'slug' => 'ai-workshop']);
    }

    public function test_creating_a_workshop_requires_a_unique_slug(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        Workshop::factory()->create(['slug' => 'ai-workshop']);

        $response = $this->actingAs($admin)->post(route('admin.events.workshops.store', $event), [
            'slug' => 'ai-workshop', 'name_ar' => 'ورشة', 'name_en' => 'AI Workshop', 'capacity' => 30,
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_admin_can_delete_a_workshop(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $workshop = Workshop::factory()->for($event)->create();

        $response = $this->actingAs($admin)->delete(route('admin.events.workshops.destroy', [$event, $workshop]));

        $response->assertRedirect(route('admin.events.workshops.index', $event));
        $this->assertDatabaseMissing('workshops', ['id' => $workshop->id]);
    }

    public function test_admin_can_view_the_index_page_with_records(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        Workshop::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.workshops.index', $event));

        $response->assertOk();
    }

    public function test_admin_can_view_the_edit_page(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $workshop = Workshop::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.workshops.edit', [$event, $workshop]));

        $response->assertOk();
    }
}
