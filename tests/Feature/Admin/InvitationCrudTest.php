<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\InvitationStatus;
use App\Models\Event;
use App\Models\Invitation;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_generates_an_invitation_for_an_active_ticket_type(): void
    {
        $event = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.events.invitations.store', $event), ['ticket_type_id' => $ticketType->id])
            ->assertRedirect(route('admin.events.invitations.index', $event));

        $invitation = Invitation::firstOrFail();
        $this->assertSame($event->id, $invitation->event_id);
        $this->assertSame($ticketType->id, $invitation->ticket_type_id);
        $this->assertSame(40, strlen($invitation->token));
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $invitation->otp);
        $this->assertTrue($invitation->expires_at->isSameDay(now()->addDays(7)));

        $this->get(route('admin.events.invitations.index', $event))
            ->assertOk()->assertSee(__('Invitation created. Copy the link and code to send to the invitee.'))
            ->assertSee($invitation->token)->assertSee($invitation->otp);
    }

    public function test_another_events_or_inactive_ticket_type_is_rejected(): void
    {
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        $foreign = TicketType::factory()->for($otherEvent)->create();
        $inactive = TicketType::factory()->for($event)->create(['is_active' => false]);
        $this->actingAs(User::factory()->create());

        foreach ([$foreign, $inactive] as $ticketType) {
            $this->post(route('admin.events.invitations.store', $event), ['ticket_type_id' => $ticketType->id])
                ->assertSessionHasErrors('ticket_type_id');
        }

        $this->assertDatabaseCount('invitations', 0);
    }

    public function test_admin_can_view_copy_and_revoke_an_invitation(): void
    {
        $event = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create();
        $invitation = Invitation::factory()->for($event)->for($ticketType)->create();
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.events.invitations.index', $event))
            ->assertOk()->assertSee($invitation->otp)->assertSee($invitation->token);

        $this->patch(route('admin.events.invitations.revoke', [$event, $invitation]))
            ->assertRedirect(route('admin.events.invitations.index', $event));
        $this->assertSame(InvitationStatus::Revoked, $invitation->fresh()->status);
    }

    public function test_admin_cannot_revoke_another_events_invitation(): void
    {
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        $ticketType = TicketType::factory()->for($otherEvent)->create();
        $invitation = Invitation::factory()->for($otherEvent)->for($ticketType)->create();

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.events.invitations.revoke', [$event, $invitation]))->assertNotFound();
    }
}
