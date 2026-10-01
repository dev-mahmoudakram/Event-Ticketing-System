<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventPageRequest;
use App\Models\Event;
use App\Models\EventPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * An event's pages: the six required policy pages (editable, never deleted or unpublished) and
 * any custom ones.
 */
class EventPageController extends Controller
{
    public function index(Event $event): View
    {
        return view('admin.event-pages.index', ['event' => $event, 'pages' => $event->pages()->get()]);
    }

    public function create(Event $event): View
    {
        return view('admin.event-pages.form', ['event' => $event, 'page' => new EventPage(['show_in_footer' => true, 'is_published' => true])]);
    }

    public function store(EventPageRequest $request, Event $event): RedirectResponse
    {
        $event->pages()->create($request->pageAttributes(null) + [
            'sort_order' => (int) $event->pages()->max('sort_order') + 1,
        ]);

        return redirect()->route('admin.events.pages.index', $event)->with('success', __('Saved.'));
    }

    public function edit(Event $event, EventPage $page): View
    {
        $this->assertBelongsToEvent($event, $page);

        return view('admin.event-pages.form', ['event' => $event, 'page' => $page]);
    }

    public function update(EventPageRequest $request, Event $event, EventPage $page): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $page);
        $page->update($request->pageAttributes($page));

        return redirect()->route('admin.events.pages.index', $event)->with('success', __('Saved.'));
    }

    public function destroy(Event $event, EventPage $page): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $page);
        abort_if($page->isRequired(), 403);

        $page->delete();

        return redirect()->route('admin.events.pages.index', $event)->with('success', __('Deleted.'));
    }

    public function reorder(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', Rule::exists('event_pages', 'id')->where('event_id', $event->id)],
        ]);

        foreach ($validated['ids'] as $position => $id) {
            EventPage::whereKey($id)->update(['sort_order' => $position]);
        }

        return response()->json(['status' => 'ok']);
    }

    private function assertBelongsToEvent(Event $event, EventPage $page): void
    {
        if ($page->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
