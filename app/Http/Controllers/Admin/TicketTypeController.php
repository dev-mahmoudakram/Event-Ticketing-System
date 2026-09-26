<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TicketTypeRequest;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TicketTypeController extends Controller
{
    public function index(Event $event): View
    {
        return view('admin.ticket-types.index', ['event' => $event, 'ticketTypes' => $event->ticketTypes]);
    }

    public function create(Event $event): View
    {
        return view('admin.ticket-types.form', ['event' => $event, 'ticketType' => new TicketType]);
    }

    public function store(TicketTypeRequest $request, Event $event): RedirectResponse
    {
        $data = $request->validated();
        $features = $this->extractFeatures($data);

        $ticketType = $event->ticketTypes()->create($data);
        $this->syncFeatures($ticketType, $features);

        return redirect()->route('admin.events.ticket-types.index', $event);
    }

    public function edit(Event $event, TicketType $ticketType): View
    {
        $this->assertBelongsToEvent($event, $ticketType);

        return view('admin.ticket-types.form', ['event' => $event, 'ticketType' => $ticketType]);
    }

    public function update(TicketTypeRequest $request, Event $event, TicketType $ticketType): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $ticketType);

        $data = $request->validated();
        $features = $this->extractFeatures($data);

        $ticketType->update($data);
        $this->syncFeatures($ticketType, $features);

        return redirect()->route('admin.events.ticket-types.index', $event);
    }

    public function destroy(Event $event, TicketType $ticketType): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $ticketType);

        // Deleting a type that has been sold or invited would take its attendees' tickets with
        // it; the Active switch hides it from sale instead.
        if ($ticketType->tickets()->exists() || $ticketType->invitations()->exists()) {
            return redirect()->route('admin.events.ticket-types.index', $event)
                ->with('error', __('This ticket type already has tickets or invitations, so it can\'t be deleted. Turn off "Active" to stop selling it.'));
        }

        $ticketType->delete();

        return redirect()->route('admin.events.ticket-types.index', $event);
    }

    private function assertBelongsToEvent(Event $event, TicketType $ticketType): void
    {
        if ($ticketType->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{ar: list<string>, en: list<string>}
     */
    private function extractFeatures(array &$data): array
    {
        $featuresAr = array_values(array_filter(array_map('trim', explode("\n", $data['features_ar'] ?? ''))));
        $featuresEn = array_values(array_filter(array_map('trim', explode("\n", $data['features_en'] ?? ''))));
        unset($data['features_ar'], $data['features_en']);

        return ['ar' => $featuresAr, 'en' => $featuresEn];
    }

    /**
     * @param  array{ar: list<string>, en: list<string>}  $features
     */
    private function syncFeatures(TicketType $ticketType, array $features): void
    {
        $ticketType->features()->delete();

        $count = max(count($features['ar']), count($features['en']));
        for ($i = 0; $i < $count; $i++) {
            $ticketType->features()->create([
                'text_ar' => $features['ar'][$i] ?? '',
                'text_en' => $features['en'][$i] ?? '',
                'sort_order' => $i,
            ]);
        }
    }
}
