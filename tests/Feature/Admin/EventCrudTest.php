<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_events_index(): void
    {
        $this->get(route('admin.events.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_the_create_page(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.events.create'));

        $response->assertOk();
    }

    public function test_admin_can_create_an_event(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.store'), [
            'slug' => 'ccs-2027',
            'name_ar' => 'قمة صناع المحتوى',
            'name_en' => 'Content Creators Summit 2027',
            'start_date' => '2027-08-15',
            'end_date' => '2027-08-16',
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('admin.events.index'));
        $this->assertDatabaseHas('events', ['slug' => 'ccs-2027']);
    }

    public function test_an_admin_sets_when_check_in_opens(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.events.store'), [
            'slug' => 'ccs-2027',
            'name_ar' => 'قمة صناع المحتوى',
            'name_en' => 'Content Creators Summit 2027',
            'start_date' => '2027-08-15',
            'end_date' => '2027-08-16',
            'check_in_starts_at' => '08:30',
            'status' => 'draft',
        ]);

        $event = Event::where('slug', 'ccs-2027')->firstOrFail();
        $this->assertSame('08:30', $event->check_in_starts_at->format('H:i'));
    }

    public function test_check_in_time_is_optional(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.events.store'), [
            'slug' => 'ccs-2027',
            'name_ar' => 'قمة صناع المحتوى',
            'name_en' => 'Content Creators Summit 2027',
            'start_date' => '2027-08-15',
            'end_date' => '2027-08-16',
            'status' => 'draft',
        ])->assertRedirect(route('admin.events.index'));

        $event = Event::where('slug', 'ccs-2027')->firstOrFail();
        $this->assertNull($event->check_in_starts_at);
    }

    public function test_creating_an_event_requires_bilingual_name(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.store'), [
            'slug' => 'ccs-2027',
            'name_ar' => '',
            'name_en' => '',
            'start_date' => '2027-08-15',
            'end_date' => '2027-08-16',
            'status' => 'draft',
        ]);

        $response->assertSessionHasErrors(['name_ar', 'name_en']);
    }

    public function test_admin_can_update_an_event(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.events.update', $event), [
            'slug' => $event->slug,
            'name_ar' => 'اسم محدث',
            'name_en' => 'Updated Name',
            'start_date' => $event->start_date->toDateString(),
            'end_date' => $event->end_date->toDateString(),
            'status' => 'published',
        ]);

        $response->assertRedirect(route('admin.events.index'));
        $this->assertDatabaseHas('events', ['id' => $event->id, 'name_en' => 'Updated Name']);
    }

    public function test_admin_can_delete_an_event(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.events.destroy', $event));

        $response->assertRedirect(route('admin.events.index'));
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_admin_can_view_the_index_page_with_records(): void
    {
        $admin = User::factory()->create();
        Event::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.events.index'));

        $response->assertOk();
    }

    public function test_admin_can_view_the_edit_page(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.events.edit', $event));

        $response->assertOk();
    }
}
