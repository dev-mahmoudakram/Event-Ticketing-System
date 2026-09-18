<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\Sponsor;
use App\Models\SponsorTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SponsorCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_sponsor_for_an_event(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $tier = SponsorTier::factory()->for($event)->create();

        $response = $this->actingAs($admin)->post(route('admin.events.sponsors.store', $event), [
            'name_ar' => 'الراعي', 'name_en' => 'Sponsor Co.', 'sponsor_tier_id' => $tier->id, 'sort_order' => 0,
        ]);

        $response->assertRedirect(route('admin.events.sponsors.index', $event));
        $this->assertDatabaseHas('sponsors', ['event_id' => $event->id, 'name_en' => 'Sponsor Co.', 'sponsor_tier_id' => $tier->id]);
    }

    public function test_creating_a_sponsor_requires_a_valid_tier(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.sponsors.store', $event), [
            'name_ar' => 'الراعي', 'name_en' => 'Sponsor Co.', 'sponsor_tier_id' => 99999,
        ]);

        $response->assertSessionHasErrors('sponsor_tier_id');
    }

    public function test_admin_can_update_a_sponsor(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $sponsor = Sponsor::factory()->for($event)->create();
        $tier = SponsorTier::factory()->for($event)->create();

        $response = $this->actingAs($admin)->put(route('admin.events.sponsors.update', [$event, $sponsor]), [
            'name_ar' => 'محدث', 'name_en' => 'Updated', 'sponsor_tier_id' => $tier->id, 'sort_order' => 0,
        ]);

        $response->assertRedirect(route('admin.events.sponsors.index', $event));
        $this->assertDatabaseHas('sponsors', ['id' => $sponsor->id, 'sponsor_tier_id' => $tier->id]);
    }

    public function test_admin_can_delete_a_sponsor(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $sponsor = Sponsor::factory()->for($event)->create();

        $response = $this->actingAs($admin)->delete(route('admin.events.sponsors.destroy', [$event, $sponsor]));

        $response->assertRedirect(route('admin.events.sponsors.index', $event));
        $this->assertDatabaseMissing('sponsors', ['id' => $sponsor->id]);
    }

    public function test_admin_can_view_the_index_page_with_records(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        Sponsor::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.sponsors.index', $event));

        $response->assertOk();
    }

    public function test_admin_can_view_the_edit_page(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $sponsor = Sponsor::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.sponsors.edit', [$event, $sponsor]));

        $response->assertOk();
    }
}
