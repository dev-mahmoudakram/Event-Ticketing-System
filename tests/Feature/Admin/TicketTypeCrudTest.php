<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTypeCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_ticket_type_with_unlimited_workshop_slots(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.ticket-types.store', $event), [
            'name_ar' => 'بلاتيني', 'name_en' => 'Platinum',
            'price' => 2500, 'currency' => 'SAR',
            'workshop_slot_count' => '', // blank = unlimited (null)
            'sort_order' => 0, 'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.events.ticket-types.index', $event));
        $this->assertDatabaseHas('ticket_types', ['event_id' => $event->id, 'name_en' => 'Platinum', 'workshop_slot_count' => null]);
    }

    public function test_admin_can_create_a_ticket_type_with_zero_workshop_slots(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.ticket-types.store', $event), [
            'name_ar' => 'عام', 'name_en' => 'General',
            'price' => 300, 'currency' => 'SAR',
            'workshop_slot_count' => 0,
            'sort_order' => 0, 'is_active' => 1,
        ]);

        $this->assertDatabaseHas('ticket_types', ['event_id' => $event->id, 'workshop_slot_count' => 0]);
    }

    public function test_admin_can_create_a_ticket_type_with_features(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.ticket-types.store', $event), [
            'name_ar' => 'عام', 'name_en' => 'General',
            'price' => 300, 'currency' => 'EGP',
            'features_ar' => "دخول كامل\nجلسات",
            'features_en' => "Full access\nSessions",
            'sort_order' => 0, 'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.events.ticket-types.index', $event));
        $ticketType = TicketType::where('name_en', 'General')->firstOrFail();
        $this->assertSame(2, $ticketType->features()->count());
        $this->assertDatabaseHas('ticket_type_features', [
            'ticket_type_id' => $ticketType->id, 'text_en' => 'Full access', 'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('ticket_type_features', [
            'ticket_type_id' => $ticketType->id, 'text_en' => 'Sessions', 'sort_order' => 1,
        ]);
    }

    public function test_updating_a_ticket_type_replaces_its_features(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create();
        $ticketType->features()->create(['text_ar' => 'قديم', 'text_en' => 'Old feature', 'sort_order' => 0]);

        $response = $this->actingAs($admin)->put(route('admin.events.ticket-types.update', [$event, $ticketType]), [
            'name_ar' => $ticketType->name_ar, 'name_en' => $ticketType->name_en,
            'price' => $ticketType->price, 'currency' => $ticketType->currency,
            'features_ar' => 'جديد', 'features_en' => 'New feature',
            'sort_order' => 0, 'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.events.ticket-types.index', $event));
        $this->assertSame(1, $ticketType->features()->count());
        $this->assertDatabaseHas('ticket_type_features', ['ticket_type_id' => $ticketType->id, 'text_en' => 'New feature']);
        $this->assertDatabaseMissing('ticket_type_features', ['ticket_type_id' => $ticketType->id, 'text_en' => 'Old feature']);
    }

    public function test_admin_can_mark_a_ticket_type_as_popular_and_on_sale(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.ticket-types.store', $event), [
            'name_ar' => 'بلاتيني', 'name_en' => 'Platinum',
            'price' => 300, 'original_price' => 450, 'currency' => 'EGP',
            'sort_order' => 0, 'is_active' => 1, 'is_popular' => 1,
        ]);

        $response->assertRedirect(route('admin.events.ticket-types.index', $event));
        $ticketType = TicketType::where('name_en', 'Platinum')->firstOrFail();
        $this->assertTrue($ticketType->is_popular);
        $this->assertSame(450, $ticketType->original_price);
        $this->assertTrue($ticketType->isOnSale());
    }

    public function test_admin_can_set_a_custom_popular_badge_label(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.ticket-types.store', $event), [
            'name_ar' => 'بلاتيني', 'name_en' => 'Platinum',
            'price' => 300, 'currency' => 'EGP',
            'sort_order' => 0, 'is_active' => 1, 'is_popular' => 1,
            'popular_label_ar' => 'الأفضل قيمة',
            'popular_label_en' => 'Best Value',
        ]);

        $response->assertRedirect(route('admin.events.ticket-types.index', $event));
        $ticketType = TicketType::where('name_en', 'Platinum')->firstOrFail();
        $this->assertSame('Best Value', $ticketType->popular_label_en);
        $this->assertSame('الأفضل قيمة', $ticketType->popular_label_ar);
    }

    public function test_leaving_the_popular_label_blank_keeps_the_default_wording(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($admin)->post(route('admin.events.ticket-types.store', $event), [
            'name_ar' => 'عام', 'name_en' => 'General',
            'price' => 300, 'currency' => 'EGP',
            'sort_order' => 0, 'is_active' => 1, 'is_popular' => 1,
        ]);

        $ticketType = TicketType::where('name_en', 'General')->firstOrFail();
        $this->assertNull($ticketType->popular_label_en);

        app()->setLocale('en');
        $this->assertSame('Most Popular', $ticketType->popularLabel());
    }

    public function test_original_price_must_be_higher_than_the_current_price(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.ticket-types.store', $event), [
            'name_ar' => 'عام', 'name_en' => 'General',
            'price' => 300, 'original_price' => 200, 'currency' => 'EGP',
            'sort_order' => 0, 'is_active' => 1,
        ]);

        $response->assertSessionHasErrors('original_price');
        $this->assertDatabaseMissing('ticket_types', ['name_en' => 'General']);
    }

    public function test_a_ticket_type_without_an_original_price_is_not_on_sale(): void
    {
        $ticketType = TicketType::factory()->create(['price' => 300, 'original_price' => null]);

        $this->assertFalse($ticketType->isOnSale());
    }

    public function test_creating_a_ticket_type_requires_a_price(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.ticket-types.store', $event), [
            'name_ar' => 'عام', 'name_en' => 'General', 'price' => '',
        ]);

        $response->assertSessionHasErrors('price');
    }

    public function test_admin_can_delete_a_ticket_type(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $response = $this->actingAs($admin)->delete(route('admin.events.ticket-types.destroy', [$event, $ticketType]));

        $response->assertRedirect(route('admin.events.ticket-types.index', $event));
        $this->assertDatabaseMissing('ticket_types', ['id' => $ticketType->id]);
    }

    public function test_admin_can_view_the_index_page_with_records(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        TicketType::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.ticket-types.index', $event));

        $response->assertOk();
    }

    public function test_admin_can_view_the_edit_page(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.ticket-types.edit', [$event, $ticketType]));

        $response->assertOk();
    }
}
