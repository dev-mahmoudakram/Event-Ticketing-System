<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TicketCheckInController extends Controller
{
    public function index(Event $event): View
    {
        return view('check-in.index', compact('event'));
    }

    public function scan(Event $event, string $ticketId): View
    {
        $ticket = Ticket::query()
            ->where('event_id', $event->id)
            ->where('ticket_id', $ticketId)
            ->first();

        if ($ticket === null) {
            return view('check-in.result', [
                'event' => $event,
                'ticket' => null,
                'result' => 'invalid',
            ]);
        }

        if (! $ticket->is_paid) {
            return view('check-in.result', compact('event', 'ticket') + ['result' => 'unpaid']);
        }

        if ($ticket->checked_in_at !== null || $ticket->status === TicketStatus::CheckedIn) {
            return view('check-in.result', compact('event', 'ticket') + ['result' => 'used']);
        }

        $checkedIn = DB::transaction(fn (): int => Ticket::query()
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

        return view('check-in.result', compact('event', 'ticket') + [
            'result' => $checkedIn === 1
                ? 'verified'
                : ($ticket->checked_in_at !== null ? 'used' : 'invalid'),
        ]);
    }

    public function store(Event $event, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'qr_code' => ['required', 'string', 'max:4096'],
        ]);
        $ticketId = $this->ticketIdFromCode($validated['qr_code']);

        if ($ticketId === null) {
            return back()->withInput()->with('error', __('Invalid QR code. Entry denied.'));
        }

        $ticket = Ticket::query()
            ->where('event_id', $event->id)
            ->where('ticket_id', $ticketId)
            ->first();

        if ($ticket === null) {
            return back()->withInput()->with('error', __('Invalid QR code. Entry denied.'));
        }

        $checkedIn = DB::transaction(fn (): int => Ticket::query()
            ->where('event_id', $event->id)
            ->whereKey($ticket->id)
            ->where('is_paid', true)
            ->where('status', TicketStatus::TicketIssued)
            ->whereNull('checked_in_at')
            ->update([
                'status' => TicketStatus::CheckedIn,
                'checked_in_at' => now(),
            ]));

        if ($checkedIn === 0) {
            return back()->withInput()->with(
                'error',
                $ticket->checked_in_at
                    ? __('This ticket has already been used. Entry denied.')
                    : __('Invalid or unpaid ticket. Entry denied.'),
            );
        }

        return redirect()->route('check-in.index', $event)
            ->with('success', __('Ticket verified. Entry allowed.'));
    }

    private function ticketIdFromCode(string $qrCode): ?string
    {
        $path = parse_url($qrCode, PHP_URL_PATH);
        $segments = is_string($path) ? explode('/', trim($path, '/')) : [];
        $ticketId = end($segments);

        if (is_string($ticketId) && preg_match('/^[A-Za-z0-9]{40}$/', $ticketId) === 1) {
            return $ticketId;
        }

        return null;
    }
}
