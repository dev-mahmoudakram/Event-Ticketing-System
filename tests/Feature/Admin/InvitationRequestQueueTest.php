<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\InvitationRequestStatus;
use App\Enums\TicketStatus;
use App\Mail\InvitationRequestReceived;
use App\Mail\InvitationRequestRejected;
use App\Mail\TicketIssued;
use App\Models\Event;
use App\Models\Invitation;
use App\Models\InvitationRequest;
use App\Models\TicketType;
use App\Models\User;
use App\Services\EventReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class InvitationRequestQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_issues_one_real_ticket_and_sends_it(): void
    {
        Mail::fake();
        [$event, $invitationRequest] = $this->makeRequest();
        $this->actingAs(User::factory()->create());
        $url = route('admin.events.invitation-requests.update-status', [$event, $invitationRequest, 'approved']);

        $this->get(route('admin.events.invitation-requests.index', $event))->assertOk();
        $this->patch($url)->assertRedirect(route('admin.events.invitation-requests.index', $event));

        $invitationRequest->refresh();
        $ticket = $invitationRequest->ticket;
        $this->assertSame(InvitationRequestStatus::Approved, $invitationRequest->status);
        $this->assertSame(TicketStatus::TicketIssued, $ticket->status);
        $this->assertTrue($ticket->is_paid);
        $this->assertSame('invitation', $ticket->payment_method);
        $this->assertNotNull($ticket->ticket_id);
        $this->assertNotNull($ticket->workshop_booking_key);
        Mail::assertSent(TicketIssued::class);

        $this->patch($url)->assertRedirect();
        $this->assertDatabaseCount('tickets', 1);
        Mail::assertSentCount(1);
    }

    public function test_an_invitation_ticket_adds_nothing_to_revenue(): void
    {
        Mail::fake();
        [$event, $invitationRequest] = $this->makeRequest();

        $this->actingAs(User::factory()->create())->patch(route(
            'admin.events.invitation-requests.update-status', [$event, $invitationRequest, 'approved']
        ))->assertSessionHas('success');

        $ticket = $invitationRequest->fresh()->ticket;
        $this->assertSame(0, (int) $ticket->price);
        $this->assertSame(0, (int) $ticket->discount_amount);

        $revenue = (new EventReport($event->fresh()))->revenue();
        $this->assertSame(0, $revenue['collected']);
        $this->assertSame(0, $revenue['discounted']);
    }

    public function test_rejection_sends_mail_without_creating_a_ticket(): void
    {
        Mail::fake();
        [$event, $invitationRequest] = $this->makeRequest();

        $this->actingAs(User::factory()->create())->patch(route(
            'admin.events.invitation-requests.update-status', [$event, $invitationRequest, 'rejected']
        ))->assertRedirect();

        $this->assertSame(InvitationRequestStatus::Rejected, $invitationRequest->fresh()->status);
        $this->assertDatabaseCount('tickets', 0);
        Mail::assertSent(InvitationRequestRejected::class);
    }

    public function test_mail_failure_rolls_back_ticket_and_leaves_request_pending(): void
    {
        [$event, $invitationRequest] = $this->makeRequest();
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('Mail unavailable'));

        $this->actingAs(User::factory()->create())->patch(route(
            'admin.events.invitation-requests.update-status', [$event, $invitationRequest, 'approved']
        ))->assertSessionHas('error');

        $this->assertSame(InvitationRequestStatus::Pending, $invitationRequest->fresh()->status);
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_admin_cannot_review_another_events_request(): void
    {
        [$event, $invitationRequest] = $this->makeRequest();
        $otherEvent = Event::factory()->create();

        $this->actingAs(User::factory()->create())->patch(route(
            'admin.events.invitation-requests.update-status', [$otherEvent, $invitationRequest, 'approved']
        ))->assertNotFound();
        $this->assertSame(InvitationRequestStatus::Pending, $invitationRequest->fresh()->status);
    }

    public function test_confirmation_and_rejection_emails_render(): void
    {
        [, $invitationRequest] = $this->makeRequest();

        $this->assertStringContainsString($invitationRequest->name, (new InvitationRequestReceived($invitationRequest))->render());
        $this->assertStringContainsString($invitationRequest->name, (new InvitationRequestRejected($invitationRequest))->render());
    }

    private function makeRequest(): array
    {
        $event = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create(['workshop_slot_count' => 1]);
        $invitation = Invitation::factory()->for($event)->for($ticketType)->create();
        $request = InvitationRequest::factory()->for($event)->for($invitation)->create();

        return [$event, $request];
    }
}
