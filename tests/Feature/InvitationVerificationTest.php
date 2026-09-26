<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\InvitationStatus;
use App\Models\Event;
use App\Models\Invitation;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_usable_link_accepts_the_correct_otp_and_sets_session(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $ticketType = TicketType::factory()->for($event)->create();
        $invitation = Invitation::factory()->for($event)->for($ticketType)->create(['otp' => '123456']);

        $this->get(route('invitations.verify', [$event, $invitation->token]))
            ->assertOk()->assertViewHas('invalid', false);
        $this->post(route('invitations.verify.attempt', [$event, $invitation->token]), ['otp' => '123456'])
            ->assertRedirect(route('invitations.create', [$event, $invitation->token]));
        $this->assertSame($invitation->id, session('invitation_verified.'.$event->id));
    }

    public function test_bad_otp_is_rejected_and_rate_limited(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $ticketType = TicketType::factory()->for($event)->create();
        $invitation = Invitation::factory()->for($event)->for($ticketType)->create(['otp' => '123456']);
        $url = route('invitations.verify.attempt', [$event, $invitation->token]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post($url, ['otp' => '000000'])->assertSessionHasErrors('otp');
        }

        $this->post($url, ['otp' => '123456'])->assertStatus(429);
        $this->assertNull(session('invitation_verified.'.$event->id));
    }

    public function test_guessing_from_many_addresses_still_locks_the_link(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $ticketType = TicketType::factory()->for($event)->create();
        $invitation = Invitation::factory()->for($event)->for($ticketType)->create(['otp' => '123456']);
        $url = route('invitations.verify.attempt', [$event, $invitation->token]);

        for ($attempt = 0; $attempt < 15; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.'.$attempt])
                ->post($url, ['otp' => '000000'])
                ->assertSessionHasErrors('otp');
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.1'])
            ->post($url, ['otp' => '123456'])
            ->assertStatus(429);
    }

    public function test_the_session_id_changes_once_the_code_is_accepted(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $ticketType = TicketType::factory()->for($event)->create();
        $invitation = Invitation::factory()->for($event)->for($ticketType)->create(['otp' => '123456']);

        $this->get(route('invitations.verify', [$event, $invitation->token]));
        $before = session()->getId();

        $this->post(route('invitations.verify.attempt', [$event, $invitation->token]), ['otp' => '123456']);

        $this->assertNotSame($before, session()->getId());
        $this->assertSame($invitation->id, session('invitation_verified.'.$event->id));
    }

    public function test_unknown_expired_used_and_revoked_links_show_the_same_invalid_page(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $ticketType = TicketType::factory()->for($event)->create();
        $invitations = [
            Invitation::factory()->for($event)->for($ticketType)->create(['expires_at' => now()->subDay()]),
            Invitation::factory()->for($event)->for($ticketType)->create(['status' => InvitationStatus::Used]),
            Invitation::factory()->for($event)->for($ticketType)->create(['status' => InvitationStatus::Revoked]),
        ];

        foreach (['unknown-token', ...array_map(fn (Invitation $invitation) => $invitation->token, $invitations)] as $token) {
            $this->get(route('invitations.verify', [$event, $token]))
                ->assertOk()->assertViewHas('invalid', true)
                ->assertSee(__('This invitation is no longer valid.'));
        }
    }

    public function test_another_events_token_does_not_resolve(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $otherEvent = Event::factory()->create(['status' => EventStatus::Published]);
        $ticketType = TicketType::factory()->for($otherEvent)->create();
        $invitation = Invitation::factory()->for($otherEvent)->for($ticketType)->create();

        $this->get(route('invitations.verify', [$event, $invitation->token]))
            ->assertOk()->assertViewHas('invalid', true);
    }
}
