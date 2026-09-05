<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\Workshop;
use App\Services\WorkshopBooker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class WorkshopBookingTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->event = Event::factory()->create(['status' => 'published', 'slug' => 'ccs-2026']);
    }

    private function ticket(?int $slots = 2, array $attributes = []): Ticket
    {
        static $n = 0;
        $n++;

        return Ticket::create(array_merge([
            'event_id' => $this->event->id,
            'ticket_type_id' => TicketType::factory()->for($this->event)->create(['workshop_slot_count' => $slots])->id,
            'name' => 'Kareem Al-Sayed',
            'email' => 'kareem'.$n.'@example.com',
            'phone' => '+20100123456'.$n,
            'ticket_number' => 'CCS2026-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'status' => TicketStatus::TicketIssued,
            'is_paid' => true,
            'workshop_booking_key' => 'AAAA-BBBB-CCCC',
        ], $attributes));
    }

    private function workshop(int $capacity = 10): Workshop
    {
        return Workshop::factory()->for($this->event)->create(['capacity' => $capacity]);
    }

    private function open(Ticket $ticket): void
    {
        $this->post(route('workshops.authenticate', $this->event), [
            'reference' => $ticket->ticket_number,
            'booking_key' => $ticket->workshop_booking_key,
        ])->assertRedirect(route('workshops.picker', $this->event));
    }

    public function test_the_reference_and_key_together_open_the_picker(): void
    {
        $ticket = $this->ticket();
        $this->workshop();

        $this->open($ticket);

        $this->get(route('workshops.picker', $this->event).'?lang=en')
            ->assertOk()
            ->assertSee($ticket->ticket_number);
    }

    public function test_a_wrong_key_is_refused_and_says_nothing_about_which_half_failed(): void
    {
        $ticket = $this->ticket();

        $response = $this->post(route('workshops.authenticate', $this->event), [
            'reference' => $ticket->ticket_number,
            'booking_key' => 'WRONG-KEY-HERE',
        ]);

        $response->assertSessionHasErrors('reference');
        $this->assertSame(
            __('We could not find a ticket with that reference and key.'),
            session('errors')->first('reference'),
        );
    }

    public function test_another_events_ticket_cannot_open_this_events_picker(): void
    {
        $ticket = $this->ticket();
        $other = Event::factory()->create(['status' => 'published']);

        $this->post(route('workshops.authenticate', $other), [
            'reference' => $ticket->ticket_number,
            'booking_key' => $ticket->workshop_booking_key,
        ])->assertSessionHasErrors('reference');
    }

    public function test_a_tier_without_workshops_is_turned_away(): void
    {
        $ticket = $this->ticket(slots: 0);

        $this->post(route('workshops.authenticate', $this->event), [
            'reference' => $ticket->ticket_number,
            'booking_key' => $ticket->workshop_booking_key,
        ])->assertSessionHasErrors('reference');
    }

    public function test_the_picker_cannot_be_opened_without_authenticating(): void
    {
        $this->get(route('workshops.picker', $this->event))->assertNotFound();
        $this->post(route('workshops.picker.store', $this->event), ['workshops' => []])->assertNotFound();
    }

    public function test_an_attendee_books_within_their_allowance(): void
    {
        $ticket = $this->ticket(slots: 2);
        $first = $this->workshop();
        $second = $this->workshop();
        $this->open($ticket);

        $this->post(route('workshops.picker.store', $this->event), ['workshops' => [$first->id, $second->id]])
            ->assertRedirect(route('workshops.picker', $this->event));

        $this->assertSame(2, $ticket->fresh()->workshopBookings()->count());
        $this->assertSame(0, $ticket->fresh()->remainingWorkshopSlots());
    }

    public function test_booking_more_than_the_allowance_is_refused(): void
    {
        $ticket = $this->ticket(slots: 1);
        $first = $this->workshop();
        $second = $this->workshop();
        $this->open($ticket);

        $this->post(route('workshops.picker.store', $this->event), ['workshops' => [$first->id, $second->id]])
            ->assertSessionHasErrors('workshops');

        $this->assertSame(0, $ticket->fresh()->workshopBookings()->count());
    }

    public function test_an_unlimited_ticket_books_everything(): void
    {
        $ticket = $this->ticket(slots: null);
        $workshops = collect(range(1, 4))->map(fn () => $this->workshop());
        $this->open($ticket);

        $this->post(route('workshops.picker.store', $this->event), ['workshops' => $workshops->pluck('id')->all()])
            ->assertSessionHasNoErrors();

        $this->assertSame(4, $ticket->fresh()->workshopBookings()->count());
    }

    public function test_deselecting_returns_the_place_to_the_pool(): void
    {
        $ticket = $this->ticket();
        $workshop = $this->workshop(capacity: 1);
        $this->open($ticket);

        $this->post(route('workshops.picker.store', $this->event), ['workshops' => [$workshop->id]]);
        $this->assertSame(0, $workshop->fresh()->remainingCapacity());

        $this->post(route('workshops.picker.store', $this->event), ['workshops' => []]);

        $this->assertSame(1, $workshop->fresh()->remainingCapacity());
        $this->assertSame(0, $ticket->fresh()->workshopBookings()->count());
    }

    public function test_a_full_workshop_cannot_be_booked(): void
    {
        $workshop = $this->workshop(capacity: 1);

        $first = $this->ticket();
        $this->open($first);
        $this->post(route('workshops.picker.store', $this->event), ['workshops' => [$workshop->id]]);

        $second = $this->ticket();
        $this->open($second);
        $this->post(route('workshops.picker.store', $this->event), ['workshops' => [$workshop->id]])
            ->assertSessionHasErrors('workshops');

        $this->assertSame(1, $workshop->fresh()->bookings()->count());
    }

    public function test_keeping_an_existing_booking_does_not_count_against_capacity_again(): void
    {
        $ticket = $this->ticket(slots: 2);
        $full = $this->workshop(capacity: 1);
        $other = $this->workshop();
        $this->open($ticket);

        $this->post(route('workshops.picker.store', $this->event), ['workshops' => [$full->id]]);

        // The workshop is now full, but this ticket already holds the place — adding a second
        // workshop must not be read as trying to take it twice.
        $this->post(route('workshops.picker.store', $this->event), ['workshops' => [$full->id, $other->id]])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $ticket->fresh()->workshopBookings()->count());
    }

    public function test_a_workshop_from_another_event_is_refused(): void
    {
        $ticket = $this->ticket();
        $foreign = Workshop::factory()->for(Event::factory()->create())->create();
        $this->open($ticket);

        $this->post(route('workshops.picker.store', $this->event), ['workshops' => [$foreign->id]])
            ->assertSessionHasErrors('workshops');
    }

    public function test_the_key_is_issued_when_the_ticket_is_issued(): void
    {
        $booker = new WorkshopBooker;

        $withSlots = $this->ticket(slots: 2, attributes: ['workshop_booking_key' => null]);
        $key = $booker->issueKeyFor($withSlots);

        $this->assertNotNull($key);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $key);
        $this->assertSame($key, $withSlots->fresh()->workshop_booking_key);
    }

    public function test_no_key_is_issued_for_a_tier_without_workshops(): void
    {
        $ticket = $this->ticket(slots: 0, attributes: ['workshop_booking_key' => null]);

        $this->assertNull((new WorkshopBooker)->issueKeyFor($ticket));
        $this->assertNull($ticket->fresh()->workshop_booking_key);
    }

    public function test_booking_is_refused_for_a_ticket_with_no_slots_even_through_the_service(): void
    {
        $ticket = $this->ticket(slots: 0);

        $this->expectException(RuntimeException::class);
        (new WorkshopBooker)->book($ticket, [$this->workshop()->id]);
    }

    public function test_starting_again_forgets_the_ticket(): void
    {
        $ticket = $this->ticket();
        $this->open($ticket);

        $this->post(route('workshops.forget', $this->event))->assertRedirect(route('workshops.book', $this->event));

        $this->get(route('workshops.picker', $this->event))->assertNotFound();
    }

    public function test_the_picker_is_not_indexed(): void
    {
        $ticket = $this->ticket();
        $this->open($ticket);

        $this->get(route('workshops.picker', $this->event))
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_an_admin_sees_who_booked_a_workshop(): void
    {
        $ticket = $this->ticket();
        $workshop = $this->workshop();
        $this->open($ticket);
        $this->post(route('workshops.picker.store', $this->event), ['workshops' => [$workshop->id]]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.events.workshops.bookings', [$this->event, $workshop]).'?lang=en');

        $response->assertOk();
        $response->assertSee($ticket->ticket_number);
        $response->assertSee('Kareem Al-Sayed');
    }

    public function test_an_admin_cannot_read_bookings_through_the_wrong_event(): void
    {
        $workshop = $this->workshop();
        $other = Event::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.events.workshops.bookings', [$other, $workshop]))
            ->assertNotFound();
    }
}
