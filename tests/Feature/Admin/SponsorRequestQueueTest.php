<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\SponsorRequestStatus;
use App\Models\Event;
use App\Models\SponsorRequest;
use App\Models\SponsorTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SponsorRequestQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_the_queue(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('admin.events.sponsor-requests.index', $event));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_sees_pending_requests_by_default(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        SponsorRequest::factory()->for($event)->create(['status' => SponsorRequestStatus::Pending, 'name_en' => 'Pending Co']);
        SponsorRequest::factory()->for($event)->create(['status' => SponsorRequestStatus::Rejected, 'name_en' => 'Rejected Co']);

        $response = $this->actingAs($admin)->get(route('admin.events.sponsor-requests.index', $event));

        $response->assertOk();
        $response->assertSee('Pending Co');
        $response->assertDontSee('Rejected Co');
    }

    public function test_admin_can_filter_by_status(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        SponsorRequest::factory()->for($event)->create(['status' => SponsorRequestStatus::Rejected, 'name_en' => 'Rejected Co']);

        $response = $this->actingAs($admin)->get(route('admin.events.sponsor-requests.index', $event).'?status=rejected');

        $response->assertOk();
        $response->assertSee('Rejected Co');
    }

    public function test_approve_moves_request_to_approved_and_creates_a_sponsor(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $tier = SponsorTier::factory()->for($event)->create(['name_en' => 'Gold']);
        $sponsorRequest = SponsorRequest::factory()->for($event)->create([
            'status' => SponsorRequestStatus::Pending,
            'name_en' => 'Acme Interiors',
            'name_ar' => 'أكمي',
            'logo_path' => 'sponsor-requests/logo.png',
            'website_url' => 'https://acme.example.com',
        ]);

        $response = $this->actingAs($admin)->patch(
            route('admin.events.sponsor-requests.update-status', [$event, $sponsorRequest, 'approved']),
            ['sponsor_tier_id' => $tier->id],
        );

        $response->assertRedirect(route('admin.events.sponsor-requests.index', $event));
        $this->assertSame(SponsorRequestStatus::Approved, $sponsorRequest->fresh()->status);
        $this->assertDatabaseHas('sponsors', [
            'event_id' => $event->id,
            'name_en' => 'Acme Interiors',
            'name_ar' => 'أكمي',
            'logo_path' => 'sponsor-requests/logo.png',
            'sponsor_tier_id' => $tier->id,
            'website_url' => 'https://acme.example.com',
        ]);
    }

    public function test_approve_without_a_tier_is_rejected(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $sponsorRequest = SponsorRequest::factory()->for($event)->create(['status' => SponsorRequestStatus::Pending]);

        $response = $this->actingAs($admin)->patch(
            route('admin.events.sponsor-requests.update-status', [$event, $sponsorRequest, 'approved']),
        );

        $response->assertSessionHasErrors('sponsor_tier_id');
        $this->assertSame(SponsorRequestStatus::Pending, $sponsorRequest->fresh()->status);
        $this->assertDatabaseCount('sponsors', 0);
    }

    public function test_reject_moves_request_to_rejected_and_creates_no_sponsor(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $sponsorRequest = SponsorRequest::factory()->for($event)->create(['status' => SponsorRequestStatus::Pending]);

        $response = $this->actingAs($admin)->patch(route('admin.events.sponsor-requests.update-status', [$event, $sponsorRequest, 'rejected']));

        $response->assertRedirect(route('admin.events.sponsor-requests.index', $event));
        $this->assertSame(SponsorRequestStatus::Rejected, $sponsorRequest->fresh()->status);
        $this->assertDatabaseCount('sponsors', 0);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $sponsorRequest = SponsorRequest::factory()->for($event)->create(['status' => SponsorRequestStatus::Pending]);

        $response = $this->actingAs($admin)->patch(route('admin.events.sponsor-requests.update-status', [$event, $sponsorRequest, 'bogus']));

        $response->assertSessionHasErrors('status');
        $this->assertSame(SponsorRequestStatus::Pending, $sponsorRequest->fresh()->status);
    }

    public function test_a_sponsor_request_from_a_different_event_404s(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        $sponsorRequest = SponsorRequest::factory()->for($otherEvent)->create(['status' => SponsorRequestStatus::Pending]);

        $response = $this->actingAs($admin)->patch(
            route('admin.events.sponsor-requests.update-status', [$event, $sponsorRequest, 'rejected']),
        );

        $response->assertStatus(404);
    }
}
