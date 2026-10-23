<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Award;
use App\Models\AwardNominee;
use App\Models\AwardVote;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\Workshop;
use App\Models\WorkshopBooking;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The tables behind Workshops (07) and Awards (08). The flows that use them are not built yet,
 * so these cover the shape and the rules the database itself enforces.
 */
class NewSchemaTest extends TestCase
{
    use RefreshDatabase;

    private function ticket(Event $event, ?int $slots = 2): Ticket
    {
        return Ticket::create([
            'event_id' => $event->id,
            'ticket_type_id' => TicketType::factory()->for($event)->create(['workshop_slot_count' => $slots])->id,
            'name' => 'Kareem Al-Sayed',
            'email' => 'kareem@example.com',
            'phone' => '+201001234567',
            'ticket_number' => 'CCS-'.fake()->unique()->numberBetween(1, 99999),
            'status' => TicketStatus::TicketIssued,
            'is_paid' => true,
        ]);
    }

    public function test_a_ticket_books_workshops(): void
    {
        $event = Event::factory()->create();
        $ticket = $this->ticket($event);
        $workshop = Workshop::factory()->for($event)->create(['capacity' => 10]);

        WorkshopBooking::create(['ticket_id' => $ticket->id, 'workshop_id' => $workshop->id]);

        $this->assertTrue($ticket->workshops->contains($workshop));
        $this->assertSame(9, $workshop->fresh()->remainingCapacity());
    }

    public function test_the_same_workshop_cannot_be_booked_twice_by_one_ticket(): void
    {
        $event = Event::factory()->create();
        $ticket = $this->ticket($event);
        $workshop = Workshop::factory()->for($event)->create();

        WorkshopBooking::create(['ticket_id' => $ticket->id, 'workshop_id' => $workshop->id]);

        $this->expectException(QueryException::class);
        WorkshopBooking::create(['ticket_id' => $ticket->id, 'workshop_id' => $workshop->id]);
    }

    public function test_a_ticket_knows_how_many_slots_it_has_left(): void
    {
        $event = Event::factory()->create();
        $ticket = $this->ticket($event, slots: 2);

        $this->assertSame(2, $ticket->remainingWorkshopSlots());

        WorkshopBooking::create(['ticket_id' => $ticket->id, 'workshop_id' => Workshop::factory()->for($event)->create()->id]);

        $this->assertSame(1, $ticket->fresh()->remainingWorkshopSlots());
    }

    public function test_a_blank_slot_count_means_unlimited_and_zero_means_none(): void
    {
        $event = Event::factory()->create();

        $unlimited = $this->ticket($event, slots: null);
        $this->assertNull($unlimited->workshopSlotAllowance());
        $this->assertNull($unlimited->remainingWorkshopSlots());
        $this->assertTrue($unlimited->canBookWorkshops());

        $none = $this->ticket($event, slots: 0);
        $this->assertSame(0, $none->workshopSlotAllowance());
        $this->assertFalse($none->canBookWorkshops());
    }

    public function test_a_workshop_with_no_capacity_set_is_unlimited(): void
    {
        $workshop = Workshop::factory()->create(['capacity' => 0]);

        $this->assertNull($workshop->remainingCapacity());
        $this->assertFalse($workshop->isFull());
    }

    public function test_a_full_workshop_says_so(): void
    {
        $event = Event::factory()->create();
        $workshop = Workshop::factory()->for($event)->create(['capacity' => 1]);
        WorkshopBooking::create(['ticket_id' => $this->ticket($event)->id, 'workshop_id' => $workshop->id]);

        $this->assertTrue($workshop->fresh()->isFull());
    }

    public function test_deleting_a_ticket_takes_its_bookings_with_it(): void
    {
        $event = Event::factory()->create();
        $ticket = $this->ticket($event);
        WorkshopBooking::create(['ticket_id' => $ticket->id, 'workshop_id' => Workshop::factory()->for($event)->create()->id]);

        $ticket->delete();

        $this->assertSame(0, WorkshopBooking::count());
    }

    public function test_a_nominee_belongs_to_a_category(): void
    {
        $award = Award::factory()->create(['name_en' => 'Best Studio']);
        $nominee = AwardNominee::factory()->for($award)->create(['name_en' => 'Atelier Rahma']);

        $this->assertSame('Best Studio', $award->name());
        $this->assertSame('Atelier Rahma', $nominee->name());
        $this->assertTrue($nominee->award->is($award));
    }

    public function test_one_email_votes_once_per_category(): void
    {
        $award = Award::factory()->create();
        $first = AwardNominee::factory()->for($award)->create();
        $second = AwardNominee::factory()->for($award)->create();

        AwardVote::create([
            'event_id' => $award->event_id, 'award_id' => $award->id,
            'award_nominee_id' => $first->id, 'email' => 'voter@example.com',
        ]);

        $this->expectException(QueryException::class);
        AwardVote::create([
            'event_id' => $award->event_id, 'award_id' => $award->id,
            'award_nominee_id' => $second->id, 'email' => 'voter@example.com',
        ]);
    }

    public function test_the_same_email_votes_in_a_different_category(): void
    {
        $event = Event::factory()->create();

        foreach (['Best Studio', 'Best Newcomer'] as $name) {
            $award = Award::factory()->for($event)->create(['name_en' => $name]);
            $nominee = AwardNominee::factory()->for($award)->create();
            AwardVote::create([
                'event_id' => $event->id, 'award_id' => $award->id,
                'award_nominee_id' => $nominee->id, 'email' => 'voter@example.com',
            ]);
        }

        $this->assertSame(2, AwardVote::count());
    }

    public function test_only_counted_votes_count(): void
    {
        $award = Award::factory()->create();
        $nominee = AwardNominee::factory()->for($award)->create();

        AwardVote::create(['event_id' => $award->event_id, 'award_id' => $award->id, 'award_nominee_id' => $nominee->id, 'email' => 'one@example.com']);
        AwardVote::create(['event_id' => $award->event_id, 'award_id' => $award->id, 'award_nominee_id' => $nominee->id, 'email' => 'two@example.com', 'removed_at' => now()]);

        $this->assertSame(2, $award->votes()->count());
        $this->assertSame(1, $award->votes()->counted()->count());
    }

    public function test_voting_is_shut_until_a_window_is_set(): void
    {
        $event = Event::factory()->create();
        $this->assertFalse($event->votingIsOpen());

        $event->update(['voting_opens_at' => now()->subDay(), 'voting_closes_at' => now()->addDay()]);
        $this->assertTrue($event->fresh()->votingIsOpen());

        $event->update(['voting_opens_at' => now()->addDay(), 'voting_closes_at' => now()->addDays(2)]);
        $this->assertFalse($event->fresh()->votingIsOpen());
    }
}
