<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScheduleListRequest;
use App\Models\Event;
use App\Models\SessionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SessionTypeController extends Controller
{
    public function index(Event $event): View
    {
        return view('admin.schedule-lists.index', $this->screen($event) + [
            'items' => $event->sessionTypes()->withCount('agendaItems')->get()
                ->each(fn (SessionType $type) => $type->setAttribute('usage_count', $type->agenda_items_count)),
        ]);
    }

    public function create(Event $event): View
    {
        return view('admin.schedule-lists.form', $this->screen($event) + ['item' => new SessionType]);
    }

    public function store(ScheduleListRequest $request, Event $event): RedirectResponse
    {
        $event->sessionTypes()->create($request->safe()->only(['name_ar', 'name_en']) + [
            'is_break' => $request->boolean('is_break'),
            'sort_order' => (int) $event->sessionTypes()->max('sort_order') + 1,
        ]);

        return redirect()->route('admin.events.session-types.index', $event)->with('success', __('Saved.'));
    }

    public function edit(Event $event, SessionType $sessionType): View
    {
        $this->assertBelongsToEvent($event, $sessionType);

        return view('admin.schedule-lists.form', $this->screen($event) + ['item' => $sessionType]);
    }

    public function update(ScheduleListRequest $request, Event $event, SessionType $sessionType): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $sessionType);
        $sessionType->update($request->safe()->only(['name_ar', 'name_en']) + ['is_break' => $request->boolean('is_break')]);

        return redirect()->route('admin.events.session-types.index', $event)->with('success', __('Saved.'));
    }

    public function destroy(Event $event, SessionType $sessionType): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $sessionType);
        $inUse = $sessionType->agendaItems()->count();

        if ($inUse > 0) {
            return redirect()->route('admin.events.session-types.index', $event)
                ->with('error', trans_choice('In use by :count session — move it first.|In use by :count sessions or workshops — move them first.', $inUse, ['count' => $inUse]));
        }

        $sessionType->delete();

        return redirect()->route('admin.events.session-types.index', $event)->with('success', __('Deleted.'));
    }

    public function reorder(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', Rule::exists('session_types', 'id')->where('event_id', $event->id)],
        ]);

        foreach ($validated['ids'] as $position => $id) {
            SessionType::whereKey($id)->update(['sort_order' => $position]);
        }

        return response()->json(['status' => 'ok']);
    }

    /** @return array<string, mixed> */
    private function screen(Event $event): array
    {
        return [
            'event' => $event,
            'routePrefix' => 'admin.events.session-types',
            'title' => __('Session Types'),
            'newLabel' => __('New Session Type'),
            'showBreakToggle' => true,
        ];
    }

    private function assertBelongsToEvent(Event $event, SessionType $sessionType): void
    {
        if ($sessionType->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
