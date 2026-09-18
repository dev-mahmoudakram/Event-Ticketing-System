<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\Sponsor;
use App\Models\SponsorTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SponsorTierCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_sponsor_tier(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.sponsor-tiers.store', $event), [
            'name_ar' => 'بلاتينيوم', 'name_en' => 'Platinum', 'sort_order' => 0,
        ]);

        $response->assertRedirect(route('admin.events.sponsor-tiers.index', $event));
        $this->assertDatabaseHas('sponsor_tiers', ['event_id' => $event->id, 'name_en' => 'Platinum']);
    }

    public function test_creating_a_sponsor_tier_requires_bilingual_name(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.sponsor-tiers.store', $event), [
            'name_ar' => '', 'name_en' => '',
        ]);

        $response->assertSessionHasErrors(['name_ar', 'name_en']);
    }

    public function test_admin_can_update_a_sponsor_tier(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $tier = SponsorTier::factory()->for($event)->create();

        $response = $this->actingAs($admin)->put(route('admin.events.sponsor-tiers.update', [$event, $tier]), [
            'name_ar' => 'ذهبي', 'name_en' => 'Gold', 'sort_order' => 1,
        ]);

        $response->assertRedirect(route('admin.events.sponsor-tiers.index', $event));
        $this->assertDatabaseHas('sponsor_tiers', ['id' => $tier->id, 'name_en' => 'Gold']);
    }

    public function test_admin_can_delete_a_sponsor_tier(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $tier = SponsorTier::factory()->for($event)->create();

        $response = $this->actingAs($admin)->delete(route('admin.events.sponsor-tiers.destroy', [$event, $tier]));

        $response->assertRedirect(route('admin.events.sponsor-tiers.index', $event));
        $this->assertDatabaseMissing('sponsor_tiers', ['id' => $tier->id]);
    }

    /**
     * sponsor_tier_id nulls out (nullOnDelete) rather than cascading, so deleting a tier
     * un-categorizes its sponsors instead of deleting them.
     */
    public function test_deleting_a_sponsor_tier_leaves_its_sponsors_untiered(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $tier = SponsorTier::factory()->for($event)->create();
        $sponsor = Sponsor::factory()->for($event)->create(['sponsor_tier_id' => $tier->id]);

        $this->actingAs($admin)->delete(route('admin.events.sponsor-tiers.destroy', [$event, $tier]));

        $this->assertNull($sponsor->fresh()->sponsor_tier_id);
    }

    public function test_admin_can_view_the_index_page_with_records(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        SponsorTier::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.sponsor-tiers.index', $event));

        $response->assertOk();
    }

    public function test_admin_can_view_the_edit_page(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $tier = SponsorTier::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.sponsor-tiers.edit', [$event, $tier]));

        $response->assertOk();
    }

    public function test_an_admin_from_another_event_cannot_edit_the_tier(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        $tier = SponsorTier::factory()->for($otherEvent)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.sponsor-tiers.edit', [$event, $tier]));

        $response->assertNotFound();
    }
}
