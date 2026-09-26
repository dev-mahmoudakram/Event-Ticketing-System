<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AgendaItemRequest;
use App\Models\AgendaItem;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AgendaItemController extends Controller
{
    public function index(Event $event): View
    {
        return view('admin.agenda-items.index', ['event' => $event, 'items' => $event->agendaItems]);
    }

    public function create(Event $event): View
    {
        return view('admin.agenda-items.form', [
            'event' => $event, 'item' => new AgendaItem,
            'speakers' => $event->speakers, 'types' => $event->sessionTypes,
        ]);
    }

    public function store(AgendaItemRequest $request, Event $event): RedirectResponse
    {
        $event->agendaItems()->create($request->validated());

        return redirect()->route('admin.events.agenda-items.index', $event);
    }

    public function edit(Event $event, AgendaItem $agendaItem): View
    {
        $this->assertBelongsToEvent($event, $agendaItem);

        return view('admin.agenda-items.form', [
            'event' => $event, 'item' => $agendaItem,
            'speakers' => $event->speakers, 'types' => $event->sessionTypes,
        ]);
    }

    public function update(AgendaItemRequest $request, Event $event, AgendaItem $agendaItem): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $agendaItem);
        $agendaItem->update($request->validated());

        return redirect()->route('admin.events.agenda-items.index', $event);
    }

    public function destroy(Event $event, AgendaItem $agendaItem): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $agendaItem);
        $agendaItem->delete();

        return redirect()->route('admin.events.agenda-items.index', $event);
    }

    private function assertBelongsToEvent(Event $event, AgendaItem $agendaItem): void
    {
        if ($agendaItem->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
