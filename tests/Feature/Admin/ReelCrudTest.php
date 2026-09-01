<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\Reel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReelCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_reels(): void
    {
        $event = Event::factory()->create();

        $this->get(route('admin.events.reels.index', $event))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_upload_a_reel(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.reels.store', $event), [
            'video' => UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4'),
            'poster' => UploadedFile::fake()->image('poster.jpg'),
            'caption_en' => 'Awards Night',
            'caption_ar' => 'ليلة الجوائز',
            'sort_order' => 1,
        ]);

        $response->assertRedirect(route('admin.events.reels.index', $event));
        $reel = Reel::where('caption_en', 'Awards Night')->firstOrFail();
        Storage::disk('public')->assertExists($reel->video_path);
        Storage::disk('public')->assertExists($reel->poster_path);
    }

    public function test_a_reel_requires_a_video(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.reels.store', $event), [
            'caption_en' => 'No clip',
        ]);

        $response->assertSessionHasErrors(['video']);
    }

    public function test_a_non_video_upload_is_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.reels.store', $event), [
            'video' => UploadedFile::fake()->image('not-a-clip.jpg'),
        ]);

        $response->assertSessionHasErrors(['video']);
    }

    public function test_editing_without_a_new_video_keeps_the_stored_clip(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $reel = Reel::factory()->for($event)->create(['video_path' => 'reels/original.mp4']);

        $this->actingAs($admin)->put(route('admin.events.reels.update', [$event, $reel]), [
            'caption_en' => 'Renamed',
            'sort_order' => 0,
        ]);

        $this->assertSame('reels/original.mp4', $reel->fresh()->video_path);
        $this->assertSame('Renamed', $reel->fresh()->caption_en);
    }

    public function test_deleting_a_reel_removes_its_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        Storage::disk('public')->put('reels/clip.mp4', 'x');
        Storage::disk('public')->put('reels/posters/p.jpg', 'x');
        $reel = Reel::factory()->for($event)->create([
            'video_path' => 'reels/clip.mp4',
            'poster_path' => 'reels/posters/p.jpg',
        ]);

        $this->actingAs($admin)->delete(route('admin.events.reels.destroy', [$event, $reel]));

        $this->assertDatabaseMissing('reels', ['id' => $reel->id]);
        Storage::disk('public')->assertMissing('reels/clip.mp4');
        Storage::disk('public')->assertMissing('reels/posters/p.jpg');
    }

    public function test_a_reel_from_another_event_is_not_reachable(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        $reel = Reel::factory()->for($otherEvent)->create();

        $this->actingAs($admin)->get(route('admin.events.reels.edit', [$event, $reel]))->assertNotFound();
    }

    public function test_a_video_over_the_configured_ceiling_is_rejected(): void
    {
        Storage::fake('public');
        config(['media.max_video_kb' => 25 * 1024]);
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.reels.store', $event), [
            'video' => UploadedFile::fake()->create('huge.mp4', 26 * 1024, 'video/mp4'),
        ]);

        $response->assertSessionHasErrors(['video']);
        $this->assertDatabaseCount('reels', 0);
    }

    public function test_a_video_within_the_ceiling_is_accepted(): void
    {
        Storage::fake('public');
        config(['media.max_video_kb' => 25 * 1024]);
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.reels.store', $event), [
            'video' => UploadedFile::fake()->create('fine.mp4', 900, 'video/mp4'),
            'caption_en' => 'Within the limit',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reels', ['caption_en' => 'Within the limit']);
    }

    public function test_the_upload_area_states_the_configured_ceiling(): void
    {
        config(['media.max_video_kb' => 25 * 1024]);
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.events.reels.create', $event).'?lang=en');

        $response->assertSee('25 MB');
    }

    public function test_admin_can_view_index_and_create_pages(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        Reel::factory()->for($event)->create();

        $this->actingAs($admin)->get(route('admin.events.reels.index', $event))->assertOk();
        $this->actingAs($admin)->get(route('admin.events.reels.create', $event))->assertOk();
    }
}
