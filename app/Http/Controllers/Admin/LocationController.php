<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScheduleListRequest;
use App\Models\Event;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LocationController extends Controller
{
    public function index(Event $event): View
    {
        return view('admin.schedule-lists.index', $this->screen($event) + [
            'items' => $event->locations()->withCount(['agendaItems', 'workshops'])->get()
                ->each(fn (Location $location) => $location->setAttribute('usage_count', $location->agenda_items_count + $location->workshops_count)),
        ]);
    }

    public function create(Event $event): View
    {
        return view('admin.schedule-lists.form', $this->screen($event) + ['item' => new Location]);
    }

    public function store(ScheduleListRequest $request, Event $event): RedirectResponse
    {
        $event->locations()->create($request->safe()->only(['name_ar', 'name_en']) + [
            'sort_order' => (int) $event->locations()->max('sort_order') + 1,
        ]);

        return redirect()->route('admin.events.locations.index', $event)->with('success', __('Saved.'));
    }

    public function edit(Event $event, Location $location): View
    {
        $this->assertBelongsToEvent($event, $location);

        return view('admin.schedule-lists.form', $this->screen($event) + ['item' => $location]);
    }

    public function update(ScheduleListRequest $request, Event $event, Location $location): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $location);
        $location->update($request->safe()->only(['name_ar', 'name_en']));

        return redirect()->route('admin.events.locations.index', $event)->with('success', __('Saved.'));
    }

    public function destroy(Event $event, Location $location): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $location);
        $inUse = $location->agendaItems()->count() + $location->workshops()->count();

        if ($inUse > 0) {
            return redirect()->route('admin.events.locations.index', $event)
                ->with('error', trans_choice('In use by :count session or workshop — move it first.|In use by :count sessions or workshops — move them first.', $inUse, ['count' => $inUse]));
        }

        $location->delete();

        return redirect()->route('admin.events.locations.index', $event)->with('success', __('Deleted.'));
    }

    public function reorder(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', Rule::exists('locations', 'id')->where('event_id', $event->id)],
        ]);

        foreach ($validated['ids'] as $position => $id) {
            Location::whereKey($id)->update(['sort_order' => $position]);
        }

        return response()->json(['status' => 'ok']);
    }

    /** @return array<string, mixed> */
    private function screen(Event $event): array
    {
        return [
            'event' => $event,
            'routePrefix' => 'admin.events.locations',
            'title' => __('Locations'),
            'newLabel' => __('New Location'),
            'showBreakToggle' => false,
        ];
    }

    private function assertBelongsToEvent(Event $event, Location $location): void
    {
        if ($location->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
