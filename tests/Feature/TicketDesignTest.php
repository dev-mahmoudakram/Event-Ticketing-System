<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Mail\TicketIssued;
use App\Mail\TicketRequestApproved;
use App\Mail\TicketRequestRejected;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketDesignTest extends TestCase
{
    use RefreshDatabase;

    private function issuedTicket(array $overrides = []): Ticket
    {
        $event = Event::factory()->create([
            'status' => 'published',
            'name_en' => 'Content Creators Summit',
            'logo_path' => '/images/ccs/ccs-logo.png',
        ]);

        return Ticket::create(array_merge([
            'event_id' => $event->id,
            'ticket_type_id' => TicketType::factory()->for($event)->create(['name_en' => 'General'])->id,
            'name' => 'Kareem Al-Sayed',
            'email' => 'kareem@example.com',
            'phone' => '+201001234567',
            'ticket_number' => 'CCS2026-000042',
            'ticket_id' => 'a-very-long-unguessable-ticket-id-000042',
            'status' => TicketStatus::TicketIssued,
            'is_paid' => true,
        ], $overrides));
    }

    public function test_the_ticket_page_shows_the_ticket(): void
    {
        $ticket = $this->issuedTicket(['workshop_booking_key' => 'WSK-4821-QJ']);

        $response = $this->get(route('tickets.show', [$ticket, $ticket->ticket_id]).'?lang=en');

        $response->assertOk();
        $response->assertSee('Kareem Al-Sayed');
        $response->assertSee('CCS2026-000042');
        $response->assertSee('WSK-4821-QJ');
        $response->assertSee('General');
        $response->assertSee('Content Creators Summit');
    }

    public function test_the_ticket_carries_both_logos(): void
    {
        $ticket = $this->issuedTicket();

        $response = $this->get(route('tickets.show', [$ticket, $ticket->ticket_id]).'?lang=en');

        $response->assertSee('/images/ccs/ccs-logo.png', false);
        $response->assertSee('creators-hub/mark.png', false);
        $response->assertSee('Powered by');
    }

    public function test_the_ticket_carries_its_check_in_code(): void
    {
        $ticket = $this->issuedTicket();

        $this->get(route('tickets.show', [$ticket, $ticket->ticket_id]).'?lang=en')
            ->assertSee('data:image/png;base64,', false);
    }

    public function test_a_ticket_link_is_not_guessable_and_is_not_indexed(): void
    {
        $ticket = $this->issuedTicket();

        $this->get(route('tickets.show', [$ticket, 'wrong-id']))->assertNotFound();
        $this->get(route('tickets.show', [$ticket, $ticket->ticket_id]))
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_a_ticket_that_was_never_issued_has_nothing_to_show(): void
    {
        $ticket = $this->issuedTicket(['status' => TicketStatus::Pending]);

        $this->get(route('tickets.show', [$ticket, $ticket->ticket_id]))->assertNotFound();
    }

    public function test_a_checked_in_ticket_still_opens(): void
    {
        $ticket = $this->issuedTicket(['status' => TicketStatus::CheckedIn, 'checked_in_at' => now()]);

        $this->get(route('tickets.show', [$ticket, $ticket->ticket_id]).'?lang=en')
            ->assertOk()
            ->assertSee('Checked in on');
    }

    public function test_the_issued_email_contains_the_ticket_and_a_link_to_it(): void
    {
        $ticket = $this->issuedTicket();
        $html = (new TicketIssued($ticket, 'fake-png-bytes'))->render();

        $this->assertStringContainsString('Kareem Al-Sayed', $html);
        $this->assertStringContainsString('CCS2026-000042', $html);
        $this->assertStringContainsString(route('tickets.show', [$ticket, $ticket->ticket_id]), $html);
        $this->assertStringContainsString('Powered by', $html);
    }

    public function test_email_images_travel_with_the_message(): void
    {
        $ticket = $this->issuedTicket();
        $html = (new TicketIssued($ticket, 'fake-png-bytes'))->render();

        // Blocked remote images would leave a ticket with no logos and no code on it.
        $this->assertSame(0, substr_count($html, 'src="http'), 'Mail images should be attached, not linked.');
        $this->assertGreaterThanOrEqual(3, substr_count($html, '<img'));
    }

    public function test_the_approval_email_leads_to_the_payment_link(): void
    {
        $ticket = $this->issuedTicket();
        $html = (new TicketRequestApproved($ticket, 'https://example.test/pay'))->render();

        $this->assertStringContainsString('https://example.test/pay', $html);
        $this->assertStringContainsString('CCS2026-000042', $html);
        $this->assertStringContainsString('Content Creators Summit', $html);
    }

    public function test_the_rejection_email_stays_on_brand(): void
    {
        $ticket = $this->issuedTicket();
        $html = (new TicketRequestRejected($ticket))->render();

        $this->assertStringContainsString('CCS2026-000042', $html);
        $this->assertStringContainsString('Powered by', $html);
    }

    public function test_the_emails_are_written_in_the_reader_language(): void
    {
        $ticket = $this->issuedTicket();
        $this->app->setLocale('ar');

        $html = (new TicketRequestApproved($ticket, 'https://example.test/pay'))->render();

        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('تمت الموافقة على طلبك.', $html);
    }
}
