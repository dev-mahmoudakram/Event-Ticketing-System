<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class TicketCheckIn
{
    /**
     * Admit an attendee, or explain why not.
     *
     * The decision is one conditional UPDATE inside a transaction: the row moves to CheckedIn
     * only while it is still paid, issued and unused. Two scanners pointed at the same ticket
     * at the same moment therefore produce exactly one admission — the database decides, not
     * a read-then-write in PHP that could interleave.
     *
     * @return array{result: string, ticket: Ticket|null}
     */
    public function admit(Event $event, string $scanned): array
    {
        $ticketId = $this->ticketIdFrom($scanned);

        if ($ticketId === null) {
            return ['result' => 'invalid', 'ticket' => null];
        }

        $ticket = Ticket::query()
            ->with('ticketType')
            ->where('event_id', $event->id)
            ->where('ticket_id', $ticketId)
            ->first();

        if ($ticket === null) {
            return ['result' => 'invalid', 'ticket' => null];
        }

        // Checked ahead of the update rather than folded into its WHERE clause: a ticket
        // scanned before doors open must say "too early", not the same "invalid or unpaid"
        // a stranger's code gets — the two need different words at the door.
        if (! $event->checkInIsOpen()) {
            return ['result' => 'too_early', 'ticket' => $ticket];
        }

        $admitted = DB::transaction(fn (): int => Ticket::query()
            ->whereKey($ticket->id)
            ->where('event_id', $event->id)
            ->where('is_paid', true)
            ->where('status', TicketStatus::TicketIssued)
            ->whereNull('checked_in_at')
            ->update([
                'status' => TicketStatus::CheckedIn,
                'checked_in_at' => now(),
            ]));

        $ticket->refresh();

        if ($admitted === 1) {
            return ['result' => 'verified', 'ticket' => $ticket];
        }

        return [
            'result' => $ticket->checked_in_at !== null ? 'used' : 'unpaid',
            'ticket' => $ticket,
        ];
    }

    /**
     * The ticket id inside whatever the scanner produced.
     *
     * Tickets issued now carry the bare id. Tickets issued before that carried a link to the
     * check-in route, and those are still in people's inboxes, so a URL is still read for the
     * id at its end rather than being turned away at the door.
     */
    public function ticketIdFrom(string $scanned): ?string
    {
        $scanned = trim($scanned);

        if (preg_match('/^[A-Za-z0-9]{40}$/', $scanned) === 1) {
            return $scanned;
        }

        $path = parse_url($scanned, PHP_URL_PATH);
        $segments = is_string($path) ? explode('/', trim($path, '/')) : [];
        $last = end($segments);

        return is_string($last) && preg_match('/^[A-Za-z0-9]{40}$/', $last) === 1 ? $last : null;
    }

    /**
     * What the person on the door should be told, per outcome.
     */
    public function message(string $result): string
    {
        return match ($result) {
            'verified' => __('Ticket verified. Entry allowed.'),
            'used' => __('This ticket has already been used. Entry denied.'),
            'unpaid' => __('Invalid or unpaid ticket. Entry denied.'),
            'too_early' => __('Check-in has not opened yet. Entry denied.'),
            default => __('Invalid QR code. Entry denied.'),
        };
    }
}
