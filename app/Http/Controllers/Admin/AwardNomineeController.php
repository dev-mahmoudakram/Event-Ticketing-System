<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AwardNomineeRequest;
use App\Models\Award;
use App\Models\AwardNominee;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The people or works nominated in one award.
 */
class AwardNomineeController extends Controller
{
    use HandlesMediaUploads;

    public function index(Event $event, Award $award): View
    {
        $this->assertAwardBelongsToEvent($event, $award);

        return view('admin.awards.nominees', [
            'event' => $event,
            'award' => $award,
            'nominees' => $award->nominees()->withCount(['votes as counted_votes_count' => fn ($query) => $query->counted()])->get(),
        ]);
    }

    public function create(Event $event, Award $award): View
    {
        $this->assertAwardBelongsToEvent($event, $award);

        return view('admin.awards.nominee-form', ['event' => $event, 'award' => $award, 'nominee' => new AwardNominee]);
    }

    public function store(AwardNomineeRequest $request, Event $event, Award $award): RedirectResponse
    {
        $this->assertAwardBelongsToEvent($event, $award);

        $data = $request->safe()->except(['image']);
        $data = $this->withUploadedMedia($data, $request, 'image', 'image_path', 'award-nominees');

        $award->nominees()->create($data + [
            'sort_order' => (int) $award->nominees()->max('sort_order') + 1,
        ]);

        return redirect()->route('admin.events.awards.nominees.index', [$event, $award])->with('success', __('Saved.'));
    }

    public function edit(Event $event, Award $award, AwardNominee $nominee): View
    {
        $this->assertNomineeBelongsToAward($event, $award, $nominee);

        return view('admin.awards.nominee-form', ['event' => $event, 'award' => $award, 'nominee' => $nominee]);
    }

    public function update(AwardNomineeRequest $request, Event $event, Award $award, AwardNominee $nominee): RedirectResponse
    {
        $this->assertNomineeBelongsToAward($event, $award, $nominee);

        $data = $request->safe()->except(['image']);
        $data = $this->withUploadedMedia($data, $request, 'image', 'image_path', 'award-nominees', $nominee->image_path);

        $nominee->update($data);

        return redirect()->route('admin.events.awards.nominees.index', [$event, $award])->with('success', __('Saved.'));
    }

    public function destroy(Event $event, Award $award, AwardNominee $nominee): RedirectResponse
    {
        $this->assertNomineeBelongsToAward($event, $award, $nominee);

        $this->deleteStoredMedia($nominee->image_path);
        $nominee->delete();

        return redirect()->route('admin.events.awards.nominees.index', [$event, $award])->with('success', __('Deleted.'));
    }

    public function reorder(Request $request, Event $event, Award $award): JsonResponse
    {
        $this->assertAwardBelongsToEvent($event, $award);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', Rule::exists('award_nominees', 'id')->where('award_id', $award->id)],
        ]);

        foreach ($validated['ids'] as $position => $id) {
            AwardNominee::whereKey($id)->update(['sort_order' => $position]);
        }

        return response()->json(['status' => 'ok']);
    }

    private function assertAwardBelongsToEvent(Event $event, Award $award): void
    {
        if ($award->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }

    private function assertNomineeBelongsToAward(Event $event, Award $award, AwardNominee $nominee): void
    {
        $this->assertAwardBelongsToEvent($event, $award);

        if ($nominee->award_id !== $award->id) {
            throw new NotFoundHttpException;
        }
    }
}
