<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\SpeakerRequestStatus;
use App\Models\Event;
use App\Models\Speaker;
use App\Models\SpeakerRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpeakerRequestQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_the_queue(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('admin.events.speaker-requests.index', $event));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_sees_pending_requests_by_default(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        SpeakerRequest::factory()->for($event)->create(['status' => SpeakerRequestStatus::Pending, 'name_en' => 'Pending Speaker']);
        SpeakerRequest::factory()->for($event)->create(['status' => SpeakerRequestStatus::Rejected, 'name_en' => 'Rejected Speaker']);

        $response = $this->actingAs($admin)->get(route('admin.events.speaker-requests.index', $event));

        $response->assertOk();
        $response->assertSee('Pending Speaker');
        $response->assertDontSee('Rejected Speaker');
    }

    public function test_admin_can_filter_by_status(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        SpeakerRequest::factory()->for($event)->create(['status' => SpeakerRequestStatus::Rejected, 'name_en' => 'Rejected Speaker']);

        $response = $this->actingAs($admin)->get(route('admin.events.speaker-requests.index', $event).'?status=rejected');

        $response->assertOk();
        $response->assertSee('Rejected Speaker');
    }

    public function test_approve_moves_request_to_approved_and_creates_a_speaker(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('speaker-requests/photo.png', 'image-bytes');
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $speakerRequest = SpeakerRequest::factory()->for($event)->create([
            'status' => SpeakerRequestStatus::Pending,
            'name_en' => 'Jane Creator',
            'name_ar' => 'جين',
            'title_en' => 'Product Designer',
            'title_ar' => 'مصممة منتجات',
            'bio_en' => 'Jane has spent a decade designing interiors.',
            'bio_ar' => 'قضت جين عقدًا في تصميم الديكورات الداخلية.',
            'photo_path' => 'speaker-requests/photo.png',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.events.speaker-requests.update-status', [$event, $speakerRequest, 'approved']));

        $response->assertRedirect(route('admin.events.speaker-requests.index', $event));
        $this->assertSame(SpeakerRequestStatus::Approved, $speakerRequest->fresh()->status);
        $this->assertDatabaseHas('speakers', [
            'event_id' => $event->id,
            'name_en' => 'Jane Creator',
            'name_ar' => 'جين',
            'title_en' => 'Product Designer',
        ]);

        // The speaker gets its own copy, so deleting it leaves the request's file alone.
        $copy = Speaker::query()->sole()->photo_path;
        $this->assertNotSame('speaker-requests/photo.png', $copy);
        $this->assertStringStartsWith('speakers/', $copy);
        Storage::disk('public')->assertExists([$copy, 'speaker-requests/photo.png']);
    }

    public function test_reject_moves_request_to_rejected_and_creates_no_speaker(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $speakerRequest = SpeakerRequest::factory()->for($event)->create(['status' => SpeakerRequestStatus::Pending]);

        $response = $this->actingAs($admin)->patch(route('admin.events.speaker-requests.update-status', [$event, $speakerRequest, 'rejected']));

        $response->assertRedirect(route('admin.events.speaker-requests.index', $event));
        $this->assertSame(SpeakerRequestStatus::Rejected, $speakerRequest->fresh()->status);
        $this->assertDatabaseCount('speakers', 0);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $speakerRequest = SpeakerRequest::factory()->for($event)->create(['status' => SpeakerRequestStatus::Pending]);

        $response = $this->actingAs($admin)->patch(route('admin.events.speaker-requests.update-status', [$event, $speakerRequest, 'bogus']));

        $response->assertSessionHasErrors('status');
        $this->assertSame(SpeakerRequestStatus::Pending, $speakerRequest->fresh()->status);
    }

    public function test_a_speaker_request_from_a_different_event_404s(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        $speakerRequest = SpeakerRequest::factory()->for($otherEvent)->create(['status' => SpeakerRequestStatus::Pending]);

        $response = $this->actingAs($admin)->patch(route('admin.events.speaker-requests.update-status', [$event, $speakerRequest, 'rejected']));

        $response->assertStatus(404);
    }

    public function test_approving_the_same_request_twice_creates_one_speaker(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $speakerRequest = SpeakerRequest::factory()->for($event)->create(['status' => SpeakerRequestStatus::Pending]);
        $url = route('admin.events.speaker-requests.update-status', [$event, $speakerRequest, 'approved']);

        $this->actingAs($admin)->patch($url)->assertSessionHas('success');
        $this->actingAs($admin)->patch($url)->assertSessionHas('error');

        $this->assertSame(1, $event->speakers()->count());
    }

    public function test_an_approved_request_cannot_be_rejected(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $speakerRequest = SpeakerRequest::factory()->for($event)->create(['status' => SpeakerRequestStatus::Approved]);

        $this->actingAs($admin)->patch(route('admin.events.speaker-requests.update-status', [$event, $speakerRequest, 'rejected']))
            ->assertSessionHas('error');

        $this->assertSame(SpeakerRequestStatus::Approved, $speakerRequest->fresh()->status);
    }
}
