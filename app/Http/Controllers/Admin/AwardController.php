<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\VotingMode;
use App\Http\Controllers\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AwardRequest;
use App\Http\Requests\Admin\AwardVotingSettingsRequest;
use App\Models\Award;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * An event's awards, plus the voting window that applies to all of them.
 */
class AwardController extends Controller
{
    use HandlesMediaUploads;

    public function index(Event $event): View
    {
        $awards = $event->awards()
            ->withCount([
                'nominees',
                'votes as counted_votes_count' => fn ($query) => $query->counted(),
            ])
            ->get();

        return view('admin.awards.index', [
            'event' => $event,
            'awards' => $awards,
            'votingModes' => VotingMode::cases(),
        ]);
    }

    public function create(Event $event): View
    {
        return view('admin.awards.form', ['event' => $event, 'award' => new Award]);
    }

    public function store(AwardRequest $request, Event $event): RedirectResponse
    {
        $event->awards()->create($request->validated() + [
            'sort_order' => (int) $event->awards()->max('sort_order') + 1,
        ]);

        return redirect()->route('admin.events.awards.index', $event)->with('success', __('Saved.'));
    }

    public function edit(Event $event, Award $award): View
    {
        $this->assertBelongsToEvent($event, $award);

        return view('admin.awards.form', ['event' => $event, 'award' => $award]);
    }

    public function update(AwardRequest $request, Event $event, Award $award): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $award);
        $award->update($request->validated());

        return redirect()->route('admin.events.awards.index', $event)->with('success', __('Saved.'));
    }

    public function destroy(Event $event, Award $award): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $award);

        foreach ($award->nominees as $nominee) {
            $this->deleteStoredMedia($nominee->image_path);
        }

        $award->delete();

        return redirect()->route('admin.events.awards.index', $event)->with('success', __('Deleted.'));
    }

    public function reorder(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', Rule::exists('awards', 'id')->where('event_id', $event->id)],
        ]);

        foreach ($validated['ids'] as $position => $id) {
            Award::whereKey($id)->update(['sort_order' => $position]);
        }

        return response()->json(['status' => 'ok']);
    }

    public function updateSettings(AwardVotingSettingsRequest $request, Event $event): RedirectResponse
    {
        $event->update([
            'voting_mode' => $request->validated('voting_mode'),
            'voting_opens_at' => $request->validated('voting_opens_at'),
            'voting_closes_at' => $request->validated('voting_closes_at'),
        ]);

        return redirect()->route('admin.events.awards.index', $event)->with('success', __('Saved.'));
    }

    private function assertBelongsToEvent(Event $event, Award $award): void
    {
        if ($award->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
