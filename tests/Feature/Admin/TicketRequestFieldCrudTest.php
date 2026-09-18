<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\TicketRequestField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketRequestFieldCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_request_field(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.request-form-fields.store', $event), [
            'type' => 'portfolio', 'label_ar' => 'ملف الأعمال', 'label_en' => 'Portfolio',
            'is_required' => 1, 'sort_order' => 0,
        ]);

        $response->assertRedirect(route('admin.events.request-form-fields.index', $event));
        $this->assertDatabaseHas('ticket_request_fields', [
            'event_id' => $event->id, 'type' => 'portfolio', 'label_en' => 'Portfolio', 'is_required' => 1,
        ]);
    }

    public function test_admin_can_update_a_request_field(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $field = TicketRequestField::factory()->for($event)->create(['label_en' => 'Old']);

        $response = $this->actingAs($admin)->put(route('admin.events.request-form-fields.update', [$event, $field]), [
            'type' => 'instagram', 'label_ar' => $field->label_ar, 'label_en' => 'New',
            'is_required' => 0, 'sort_order' => 1,
        ]);

        $response->assertRedirect(route('admin.events.request-form-fields.index', $event));
        $this->assertDatabaseHas('ticket_request_fields', ['id' => $field->id, 'label_en' => 'New']);
    }

    public function test_admin_can_delete_a_request_field(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $field = TicketRequestField::factory()->for($event)->create();

        $response = $this->actingAs($admin)->delete(route('admin.events.request-form-fields.destroy', [$event, $field]));

        $response->assertRedirect(route('admin.events.request-form-fields.index', $event));
        $this->assertDatabaseMissing('ticket_request_fields', ['id' => $field->id]);
    }

    public function test_admin_can_create_a_social_link_field_with_follower_count(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.request-form-fields.store', $event), [
            'type' => 'social_link', 'platform' => 'tiktok', 'show_follower_count' => 1,
            'label_ar' => 'تيك توك', 'label_en' => 'TikTok', 'is_required' => 1, 'sort_order' => 0,
        ]);

        $response->assertRedirect(route('admin.events.request-form-fields.index', $event));
        $this->assertDatabaseHas('ticket_request_fields', [
            'event_id' => $event->id, 'type' => 'social_link', 'platform' => 'tiktok', 'show_follower_count' => 1,
        ]);
    }

    public function test_a_social_link_field_requires_a_valid_platform(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.request-form-fields.store', $event), [
            'type' => 'social_link', 'platform' => 'not-a-real-platform', 'label_ar' => 'x', 'label_en' => 'x',
        ]);

        $response->assertSessionHasErrors('platform');
    }

    public function test_creating_a_request_field_requires_a_valid_type(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.request-form-fields.store', $event), [
            'type' => 'not-a-real-type', 'label_ar' => 'x', 'label_en' => 'x',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_admin_can_view_the_index_page(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        TicketRequestField::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.request-form-fields.index', $event));

        $response->assertOk();
    }

    public function test_a_request_field_from_another_event_returns_404_on_edit(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        $field = TicketRequestField::factory()->for($otherEvent)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.request-form-fields.edit', [$event, $field]));

        $response->assertStatus(404);
    }
}
