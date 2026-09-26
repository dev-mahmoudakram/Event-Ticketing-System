<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\SpeakerRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\SpeakerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SpeakerRequestController extends Controller
{
    public function index(Event $event, Request $request): View
    {
        $status = $request->query('status', SpeakerRequestStatus::Pending->value);

        $speakerRequests = $event->speakerRequests()
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->get();

        return view('admin.speaker-requests.index', ['event' => $event, 'speakerRequests' => $speakerRequests, 'status' => $status]);
    }

    public function updateStatus(Event $event, SpeakerRequest $speakerRequest, string $status): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $speakerRequest);

        $status = Validator::make(
            ['status' => $status],
            ['status' => ['required', 'in:approved,rejected']],
        )->validate()['status'];

        // Locked and re-checked so approving twice can't add the same speaker twice, and an
        // already-decided request can't be flipped.
        $changed = DB::transaction(function () use ($event, $speakerRequest, $status): bool {
            $locked = SpeakerRequest::query()->lockForUpdate()->findOrFail($speakerRequest->id);

            if ($locked->status !== SpeakerRequestStatus::Pending) {
                return false;
            }

            if ($status === 'approved') {
                $event->speakers()->create([
                    'name_ar' => $locked->name_ar,
                    'name_en' => $locked->name_en,
                    'title_ar' => $locked->title_ar,
                    'title_en' => $locked->title_en,
                    'bio_ar' => $locked->bio_ar,
                    'bio_en' => $locked->bio_en,
                    'photo_path' => $locked->photo_path,
                    'sort_order' => $event->speakers()->max('sort_order') + 1,
                ]);
            }

            $locked->update([
                'status' => $status === 'approved' ? SpeakerRequestStatus::Approved : SpeakerRequestStatus::Rejected,
            ]);

            return true;
        });

        $message = $status === 'approved'
            ? __('Speaker request approved and added to speakers.')
            : __('Speaker request rejected successfully.');

        return redirect()
            ->route('admin.events.speaker-requests.index', $event)
            ->with($changed ? 'success' : 'error', $changed ? $message : __('This request has already been reviewed.'));
    }

    private function assertBelongsToEvent(Event $event, SpeakerRequest $speakerRequest): void
    {
        if ($speakerRequest->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
