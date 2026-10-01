<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Mail\TicketRequestApproved;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTermsAgreementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(TicketType $type, array $overrides = []): array
    {
        return $overrides + ['ticket_type_id' => $type->id, 'name' => 'Sara Ali', 'email' => 'sara@example.com', 'phone' => '+201001234567'];
    }

    public function test_a_request_without_agreeing_is_refused(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $type = TicketType::factory()->for($event)->create();

        $this->post(route('ticket-requests.store', $event), $this->payload($type))->assertSessionHasErrors('accept_terms');

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_agreeing_records_when(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $type = TicketType::factory()->for($event)->create();
        $this->travelTo(now()->setTime(12, 0));

        $this->post(route('ticket-requests.store', $event), $this->payload($type, ['accept_terms' => '1']))->assertSessionHasNoErrors();

        $this->assertSame(now()->toDateTimeString(), Ticket::sole()->terms_accepted_at->toDateTimeString());
    }

    public function test_the_form_links_both_policies(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        TicketType::factory()->for($event)->create();

        $this->get(route('landing.show', $event).'?lang=en')
            ->assertSee('name="accept_terms"', false)
            ->assertSee(route('event-pages.show', [$event, 'terms']), false)
            ->assertSee(route('event-pages.show', [$event, 'refund-policy']), false);
    }

    public function test_the_approval_email_links_both_policies(): void
    {
        $ticket = Ticket::factory()->create();

        $html = (new TicketRequestApproved($ticket, 'https://pay.example/1'))->render();

        $this->assertStringContainsString(route('event-pages.show', [$ticket->event, 'terms']), $html);
        $this->assertStringContainsString(route('event-pages.show', [$ticket->event, 'refund-policy']), $html);
    }
}
