<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use App\Services\WorkshopBooker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WorkshopBookingController extends Controller
{
    public function __construct(private readonly WorkshopBooker $booker) {}

    /**
     * The door: a reference number and the booking key that came with the ticket.
     */
    public function create(Event $event): View
    {
        return view('workshops.book', ['event' => $event]);
    }

    public function authenticate(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:40'],
            'booking_key' => ['required', 'string', 'max:40'],
        ]);

        $ticket = $this->booker->authenticate($event, $validated['reference'], $validated['booking_key']);

        if ($ticket === null) {
            // One message for both halves: saying which was wrong helps someone guessing.
            throw ValidationException::withMessages([
                'reference' => __('We could not find a ticket with that reference and key.'),
            ]);
        }

        if (! $ticket->canBookWorkshops()) {
            throw ValidationException::withMessages([
                'reference' => __('This ticket does not include workshops.'),
            ]);
        }

        // The pair has been checked; the session carries it from here so the key is not in the
        // address bar of every page.
        $request->session()->put($this->sessionKey($event), $ticket->id);

        return redirect()->route('workshops.picker', $event);
    }

    public function picker(Request $request, Event $event): View
    {
        $ticket = $this->ticketFromSession($request, $event);

        return view('workshops.picker', [
            'event' => $event,
            'ticket' => $ticket,
            'workshops' => $this->booker->availableFor($ticket),
            'chosen' => $ticket->workshopBookings()->pluck('workshop_id')->all(),
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $ticket = $this->ticketFromSession($request, $event);

        $validated = $request->validate([
            'workshops' => ['array'],
            'workshops.*' => ['integer'],
        ]);

        try {
            $this->booker->book($ticket, $validated['workshops'] ?? []);
        } catch (RuntimeException $e) {
            return back()->withErrors(['workshops' => $e->getMessage()]);
        }

        return redirect()
            ->route('workshops.picker', $event)
            ->with('workshop_booking_saved', true);
    }

    public function forget(Request $request, Event $event): RedirectResponse
    {
        $request->session()->forget($this->sessionKey($event));

        return redirect()->route('workshops.book', $event);
    }

    private function ticketFromSession(Request $request, Event $event): Ticket
    {
        $ticketId = $request->session()->get($this->sessionKey($event));

        $ticket = $ticketId === null
            ? null
            : $event->tickets()->with('ticketType')->find($ticketId);

        if ($ticket === null) {
            throw new NotFoundHttpException;
        }

        return $ticket;
    }

    private function sessionKey(Event $event): string
    {
        return 'workshop_booking.'.$event->id;
    }
}
