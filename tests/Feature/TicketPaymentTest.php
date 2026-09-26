<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Mail\TicketIssued;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TicketPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function paymentPendingTicket(int $workshopSlots = 0): Ticket
    {
        $event = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create(['workshop_slot_count' => $workshopSlots]);

        return Ticket::factory()->for($event)->for($ticketType)->create([
            'status' => TicketStatus::PaymentPending,
            'email' => 'attendee@example.com',
            'ticket_id' => null,
            'is_paid' => false,
        ]);
    }

    private function paymentUrl(Ticket $ticket): string
    {
        return URL::temporarySignedRoute('tickets.payment', now()->addDays(7), ['ticket' => $ticket]);
    }

    public function test_paying_issues_the_ticket_and_emails_it(): void
    {
        Mail::fake();
        $ticket = $this->paymentPendingTicket(workshopSlots: 1);

        $this->get($this->paymentUrl($ticket))->assertOk();

        $ticket->refresh();
        $this->assertSame(TicketStatus::TicketIssued, $ticket->status);
        $this->assertTrue($ticket->is_paid);
        $this->assertSame('payment_link', $ticket->payment_method);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{40}$/', $ticket->ticket_id);
        $this->assertNotNull($ticket->workshop_booking_key);
        Mail::assertSent(TicketIssued::class, fn (TicketIssued $mail) => $mail->hasTo('attendee@example.com'));
    }

    public function test_a_tier_without_workshops_gets_no_booking_key(): void
    {
        Mail::fake();
        $ticket = $this->paymentPendingTicket(workshopSlots: 0);

        $this->get($this->paymentUrl($ticket))->assertOk();

        $this->assertNull($ticket->fresh()->workshop_booking_key);
    }

    public function test_an_unsigned_link_is_refused(): void
    {
        Mail::fake();
        $ticket = $this->paymentPendingTicket();

        $this->get(route('tickets.payment', $ticket))->assertForbidden();

        $this->assertSame(TicketStatus::PaymentPending, $ticket->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_an_expired_link_is_refused(): void
    {
        Mail::fake();
        $ticket = $this->paymentPendingTicket();
        $url = $this->paymentUrl($ticket);

        $this->travel(8)->days();

        $this->get($url)->assertForbidden();
        $this->assertSame(TicketStatus::PaymentPending, $ticket->fresh()->status);
    }

    public function test_opening_the_link_again_does_not_reissue_the_ticket(): void
    {
        Mail::fake();
        $ticket = $this->paymentPendingTicket();
        $url = $this->paymentUrl($ticket);

        $this->get($url)->assertOk();
        $firstTicketId = $ticket->fresh()->ticket_id;

        $this->get($url)->assertOk();

        $this->assertSame($firstTicketId, $ticket->fresh()->ticket_id);
        Mail::assertSentCount(1);
    }
}
