<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\TicketCheckIn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketCheckInController extends Controller
{
    public function __construct(private readonly TicketCheckIn $checkIn) {}

    public function index(Event $event): View
    {
        return view('check-in.index', [
            'event' => $event,
            'arrived' => $event->tickets()->whereNotNull('checked_in_at')->count(),
            'expected' => $event->tickets()->where('is_paid', true)->count(),
        ]);
    }

    /**
     * The camera's endpoint: one scan in, one verdict out, page never reloads.
     */
    public function scan(Event $event, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qr_code' => ['required', 'string', 'max:4096'],
        ]);

        ['result' => $result, 'ticket' => $ticket] = $this->checkIn->admit($event, $validated['qr_code']);

        return response()->json([
            'result' => $result,
            'message' => $this->checkIn->message($result),
            'ticket' => $ticket === null ? null : [
                'name' => $ticket->name,
                'reference' => $ticket->ticket_number,
                'type' => app()->getLocale() === 'ar'
                    ? $ticket->ticketType?->name_ar
                    : $ticket->ticketType?->name_en,
                'checked_in_at' => $ticket->checked_in_at?->format('H:i'),
            ],
        ]);
    }

    /**
     * The same decision without JavaScript, for a hardware scanner typing into the box or a
     * code too damaged for the camera to read.
     */
    public function store(Event $event, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'qr_code' => ['required', 'string', 'max:4096'],
        ]);

        ['result' => $result, 'ticket' => $ticket] = $this->checkIn->admit($event, $validated['qr_code']);

        if ($result !== 'verified') {
            return back()->withInput()->with('error', $this->checkIn->message($result));
        }

        return redirect()->route('check-in.index', $event)
            ->with('success', $this->checkIn->message($result))
            ->with('checked_in_name', $ticket?->name);
    }
}
