<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WorkshopRequest;
use App\Models\Event;
use App\Models\Workshop;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WorkshopController extends Controller
{
    public function index(Event $event): View
    {
        return view('admin.workshops.index', [
            'event' => $event,
            'workshops' => $event->workshops()->withCount('bookings')->get(),
        ]);
    }

    /**
     * Who is coming to this workshop — the list the room needs on the day.
     */
    public function bookings(Event $event, Workshop $workshop): View
    {
        if ($workshop->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }

        return view('admin.workshops.bookings', [
            'event' => $event,
            'workshop' => $workshop->loadCount('bookings'),
            'bookings' => $workshop->bookings()->with('ticket.ticketType')->orderBy('booked_at')->get(),
        ]);
    }

    public function create(Event $event): View
    {
        return view('admin.workshops.form', [
            'event' => $event, 'workshop' => new Workshop, 'speakers' => $event->speakers,
        ]);
    }

    public function store(WorkshopRequest $request, Event $event): RedirectResponse
    {
        $event->workshops()->create($request->validated());

        return redirect()->route('admin.events.workshops.index', $event);
    }

    public function edit(Event $event, Workshop $workshop): View
    {
        $this->assertBelongsToEvent($event, $workshop);

        return view('admin.workshops.form', [
            'event' => $event, 'workshop' => $workshop, 'speakers' => $event->speakers,
        ]);
    }

    public function update(WorkshopRequest $request, Event $event, Workshop $workshop): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $workshop);
        $workshop->update($request->validated());

        return redirect()->route('admin.events.workshops.index', $event);
    }

    public function destroy(Event $event, Workshop $workshop): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $workshop);
        $workshop->delete();

        return redirect()->route('admin.events.workshops.index', $event);
    }

    private function assertBelongsToEvent(Event $event, Workshop $workshop): void
    {
        if ($workshop->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
