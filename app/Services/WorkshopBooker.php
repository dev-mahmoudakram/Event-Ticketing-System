<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\Workshop;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class WorkshopBooker
{
    /**
     * The key an attendee types alongside their ticket number to open the picker.
     *
     * Issued with the ticket, and only for a tier that actually includes workshops. Grouped in
     * fours because it is read off a screen or a printout and typed in by hand.
     */
    public function issueKeyFor(Ticket $ticket): ?string
    {
        if (! $ticket->canBookWorkshops()) {
            return null;
        }

        $key = collect(range(1, 3))
            ->map(fn () => Str::upper(Str::random(4)))
            ->implode('-');

        $ticket->update(['workshop_booking_key' => $key]);

        return $key;
    }

    /**
     * Find the ticket behind a reference number and a booking key.
     *
     * The comparison is timing-safe and both halves must match the same ticket, so the pair is
     * the credential rather than the reference number alone — reference numbers are sequential
     * and appear in emails.
     */
    public function authenticate(Event $event, string $reference, string $key): ?Ticket
    {
        $ticket = $event->tickets()
            ->with('ticketType')
            ->where('ticket_number', trim($reference))
            ->first();

        if ($ticket === null || blank($ticket->workshop_booking_key)) {
            return null;
        }

        $given = Str::upper(trim($key));

        return hash_equals($ticket->workshop_booking_key, $given) ? $ticket : null;
    }

    /**
     * The workshops this ticket may choose from, with what is left of each.
     *
     * @return Collection<int, Workshop>
     */
    public function availableFor(Ticket $ticket): Collection
    {
        return $ticket->event->workshops()->withCount('bookings')->get();
    }

    /**
     * Replace this ticket's bookings with exactly the workshops chosen.
     *
     * Runs in a transaction with the workshop rows locked, so two attendees claiming the last
     * place at the same moment cannot both get it — the second one's capacity check sees the
     * first one's booking. Deselecting is allowed: a place given up goes back to the pool.
     *
     * @param  list<int>  $workshopIds
     *
     * @throws RuntimeException when the choice breaks the slot allowance or a workshop is full
     */
    public function book(Ticket $ticket, array $workshopIds): void
    {
        $workshopIds = array_values(array_unique(array_map('intval', $workshopIds)));

        $allowance = $ticket->workshopSlotAllowance();

        if ($allowance === 0) {
            throw new RuntimeException(__('This ticket does not include workshops.'));
        }

        if ($allowance !== null && count($workshopIds) > $allowance) {
            throw new RuntimeException(trans_choice(
                '{1} This ticket includes one workshop.|[2,*] This ticket includes :count workshops.',
                $allowance,
                ['count' => $allowance],
            ));
        }

        DB::transaction(function () use ($ticket, $workshopIds) {
            $workshops = Workshop::query()
                ->where('event_id', $ticket->event_id)
                ->whereIn('id', $workshopIds)
                ->lockForUpdate()
                ->get();

            if ($workshops->count() !== count($workshopIds)) {
                throw new RuntimeException(__('One of those workshops is not part of this event.'));
            }

            $keeping = $ticket->workshopBookings()->pluck('workshop_id')->all();
            $ticket->workshopBookings()->whereNotIn('workshop_id', $workshopIds)->delete();

            foreach ($workshops as $workshop) {
                if (in_array($workshop->id, $keeping, true)) {
                    continue;
                }

                if ($workshop->isFull()) {
                    throw new RuntimeException(__(':workshop is full.', ['workshop' => $workshop->name()]));
                }

                $ticket->workshopBookings()->create([
                    'workshop_id' => $workshop->id,
                    'booked_at' => now(),
                ]);
            }
        });
    }
}
