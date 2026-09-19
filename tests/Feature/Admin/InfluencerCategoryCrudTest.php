<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\InfluencerCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfluencerCategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_influencer_category(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.influencer-categories.store', $event), [
            'name_ar' => 'طعام', 'name_en' => 'Food', 'sort_order' => 0,
        ]);

        $response->assertRedirect(route('admin.events.influencer-categories.index', $event));
        $this->assertDatabaseHas('influencer_categories', ['event_id' => $event->id, 'name_en' => 'Food']);
    }

    public function test_creating_a_category_requires_bilingual_name(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.influencer-categories.store', $event), [
            'name_ar' => '', 'name_en' => '',
        ]);

        $response->assertSessionHasErrors(['name_ar', 'name_en']);
    }

    public function test_admin_can_update_a_category(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $category = InfluencerCategory::factory()->for($event)->create();

        $response = $this->actingAs($admin)->put(route('admin.events.influencer-categories.update', [$event, $category]), [
            'name_ar' => 'موضة', 'name_en' => 'Fashion', 'sort_order' => 1,
        ]);

        $response->assertRedirect(route('admin.events.influencer-categories.index', $event));
        $this->assertDatabaseHas('influencer_categories', ['id' => $category->id, 'name_en' => 'Fashion']);
    }

    public function test_admin_can_delete_a_category(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $category = InfluencerCategory::factory()->for($event)->create();

        $response = $this->actingAs($admin)->delete(route('admin.events.influencer-categories.destroy', [$event, $category]));

        $response->assertRedirect(route('admin.events.influencer-categories.index', $event));
        $this->assertDatabaseMissing('influencer_categories', ['id' => $category->id]);
    }

    public function test_admin_can_view_the_index_page_with_records(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        InfluencerCategory::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.influencer-categories.index', $event));

        $response->assertOk();
    }

    public function test_admin_can_view_the_edit_page(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $category = InfluencerCategory::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.influencer-categories.edit', [$event, $category]));

        $response->assertOk();
    }

    public function test_an_admin_from_another_event_cannot_edit_the_category(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        $category = InfluencerCategory::factory()->for($otherEvent)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.influencer-categories.edit', [$event, $category]));

        $response->assertNotFound();
    }

    public function test_admin_can_require_influencer_category_on_ticket_requests(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create(['require_influencer_category' => false]);

        $response = $this->actingAs($admin)->patch(route('admin.events.influencer-categories.update-settings', $event), [
            'require_influencer_category' => 1,
        ]);

        $response->assertRedirect(route('admin.events.influencer-categories.index', $event));
        $this->assertTrue($event->fresh()->require_influencer_category);
    }

    public function test_admin_can_make_influencer_category_optional_again(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create(['require_influencer_category' => true]);

        $this->actingAs($admin)->patch(route('admin.events.influencer-categories.update-settings', $event), []);

        $this->assertFalse($event->fresh()->require_influencer_category);
    }
}
