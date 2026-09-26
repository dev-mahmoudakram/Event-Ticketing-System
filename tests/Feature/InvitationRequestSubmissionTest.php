<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\InvitationStatus;
use App\Mail\InvitationRequestReceived;
use App\Models\Event;
use App\Models\InfluencerCategory;
use App\Models\Invitation;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvitationRequestSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_requires_a_verified_otp(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $ticketType = TicketType::factory()->for($event)->create();
        $invitation = Invitation::factory()->for($event)->for($ticketType)->create();

        $this->get(route('invitations.create', [$event, $invitation->token]))
            ->assertRedirect(route('invitations.verify', [$event, $invitation->token]));
    }

    public function test_verified_invitee_submits_fixed_fields_once_and_gets_confirmation(): void
    {
        Mail::fake();
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $ticketType = TicketType::factory()->for($event)->create();
        $invitation = Invitation::factory()->for($event)->for($ticketType)->create();
        $session = ['invitation_verified.'.$event->id => $invitation->id];
        $url = route('invitations.store', [$event, $invitation->token]);
        $payload = [
            'name' => 'Sara Ali', 'email' => 'sara@example.com', 'phone' => '+201001234567',
            'influencer_category_id' => 'other', 'influencer_category_other' => 'Podcast Host',
            'instagram_url' => 'https://instagram.com/sara', 'instagram_followers' => 5000,
            'facebook_url' => 'https://facebook.com/sara', 'facebook_followers' => 1200,
            'tiktok_url' => 'https://tiktok.com/@sara', 'tiktok_followers' => 8000,
        ];

        $this->withSession($session)->get(route('invitations.create', [$event, $invitation->token]))->assertOk();
        $this->withSession($session)->post($url, $payload)->assertRedirect(route('landing.show', $event));
        $this->assertDatabaseHas('invitation_requests', [
            'invitation_id' => $invitation->id, 'event_id' => $event->id,
            'influencer_category_id' => null, 'influencer_category_other' => 'Podcast Host',
            'instagram_followers' => 5000, 'facebook_followers' => 1200, 'tiktok_followers' => 8000,
        ]);
        $this->assertSame(InvitationStatus::Used, $invitation->fresh()->status);
        Mail::assertSent(InvitationRequestReceived::class);

        $this->withSession($session)->post($url, $payload)
            ->assertRedirect(route('invitations.verify', [$event, $invitation->token]));
        $this->assertDatabaseCount('invitation_requests', 1);
    }

    public function test_required_category_and_invalid_social_values_are_rejected(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published, 'require_influencer_category' => true]);
        $ticketType = TicketType::factory()->for($event)->create();
        $invitation = Invitation::factory()->for($event)->for($ticketType)->create();
        $otherEvent = Event::factory()->create();
        $foreignCategory = InfluencerCategory::factory()->for($otherEvent)->create();
        $url = route('invitations.store', [$event, $invitation->token]);
        $session = ['invitation_verified.'.$event->id => $invitation->id];

        $this->withSession($session)->post($url, [
            'name' => 'Sara Ali', 'email' => 'sara@example.com', 'phone' => '+201001234567',
        ])->assertSessionHasErrors('influencer_category_id');

        $this->withSession($session)->post($url, [
            'name' => 'Sara Ali', 'email' => 'sara@example.com', 'phone' => '+201001234567',
            'influencer_category_id' => $foreignCategory->id,
            'instagram_url' => 'javascript:alert(1)', 'instagram_followers' => -1,
        ])->assertSessionHasErrors(['influencer_category_id', 'instagram_url', 'instagram_followers']);

        $this->assertSame(InvitationStatus::Unused, $invitation->fresh()->status);
    }
}
