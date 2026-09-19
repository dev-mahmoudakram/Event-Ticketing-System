<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\Speaker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpeakerCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_speaker_for_an_event(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.speakers.store', $event), [
            'name_ar' => 'اسم المتحدث',
            'name_en' => 'Speaker Name',
            'sort_order' => 0,
        ]);

        $response->assertRedirect(route('admin.events.speakers.index', $event));
        $this->assertDatabaseHas('speakers', ['event_id' => $event->id, 'name_en' => 'Speaker Name']);
    }

    public function test_creating_a_speaker_requires_bilingual_name(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.speakers.store', $event), [
            'name_ar' => '', 'name_en' => '',
        ]);

        $response->assertSessionHasErrors(['name_ar', 'name_en']);
    }

    public function test_admin_can_update_a_speaker(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $speaker = Speaker::factory()->for($event)->create();

        $response = $this->actingAs($admin)->put(route('admin.events.speakers.update', [$event, $speaker]), [
            'name_ar' => 'محدث', 'name_en' => 'Updated', 'sort_order' => 1,
        ]);

        $response->assertRedirect(route('admin.events.speakers.index', $event));
        $this->assertDatabaseHas('speakers', ['id' => $speaker->id, 'name_en' => 'Updated']);
    }

    public function test_admin_can_delete_a_speaker(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $speaker = Speaker::factory()->for($event)->create();

        $response = $this->actingAs($admin)->delete(route('admin.events.speakers.destroy', [$event, $speaker]));

        $response->assertRedirect(route('admin.events.speakers.index', $event));
        $this->assertDatabaseMissing('speakers', ['id' => $speaker->id]);
    }

    public function test_admin_can_view_the_index_page_with_records(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        Speaker::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.speakers.index', $event));

        $response->assertOk();
    }

    public function test_admin_can_view_the_edit_page(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $speaker = Speaker::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.speakers.edit', [$event, $speaker]));

        $response->assertOk();
    }

    public function test_editing_a_speaker_from_another_event_returns_404(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $otherSpeaker = Speaker::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.events.speakers.edit', [$event, $otherSpeaker]));

        $response->assertStatus(404);
    }

    public function test_admin_can_mark_a_speaker_as_featured(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.speakers.store', $event), [
            'name_ar' => 'اسم', 'name_en' => 'Speaker Name', 'sort_order' => 0, 'is_featured' => 1,
        ]);

        $response->assertRedirect(route('admin.events.speakers.index', $event));
        $this->assertDatabaseHas('speakers', ['event_id' => $event->id, 'name_en' => 'Speaker Name', 'is_featured' => 1]);
    }

    public function test_a_fifth_featured_speaker_is_rejected(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        Speaker::factory()->for($event)->count(4)->create(['is_featured' => true]);

        $response = $this->actingAs($admin)->post(route('admin.events.speakers.store', $event), [
            'name_ar' => 'اسم', 'name_en' => 'Fifth Speaker', 'sort_order' => 0, 'is_featured' => 1,
        ]);

        $response->assertSessionHasErrors('is_featured');
        $this->assertDatabaseMissing('speakers', ['event_id' => $event->id, 'name_en' => 'Fifth Speaker']);
    }

    public function test_updating_an_already_featured_speaker_does_not_count_against_itself(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        Speaker::factory()->for($event)->count(3)->create(['is_featured' => true]);
        $speaker = Speaker::factory()->for($event)->create(['is_featured' => true]);

        $response = $this->actingAs($admin)->put(route('admin.events.speakers.update', [$event, $speaker]), [
            'name_ar' => $speaker->name_ar, 'name_en' => $speaker->name_en, 'sort_order' => 0, 'is_featured' => 1,
        ]);

        $response->assertRedirect(route('admin.events.speakers.index', $event));
        $this->assertDatabaseHas('speakers', ['id' => $speaker->id, 'is_featured' => 1]);
    }

    public function test_featured_count_is_scoped_per_event(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        Speaker::factory()->for($otherEvent)->count(4)->create(['is_featured' => true]);

        $response = $this->actingAs($admin)->post(route('admin.events.speakers.store', $event), [
            'name_ar' => 'اسم', 'name_en' => 'Speaker Name', 'sort_order' => 0, 'is_featured' => 1,
        ]);

        $response->assertRedirect(route('admin.events.speakers.index', $event));
        $this->assertDatabaseHas('speakers', ['event_id' => $event->id, 'name_en' => 'Speaker Name', 'is_featured' => 1]);
    }
}
