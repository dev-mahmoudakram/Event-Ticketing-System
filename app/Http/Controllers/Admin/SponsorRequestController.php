<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\SponsorRequestStatus;
use App\Http\Controllers\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\SponsorRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SponsorRequestController extends Controller
{
    use HandlesMediaUploads;

    public function index(Event $event, Request $request): View
    {
        $status = $request->query('status', SponsorRequestStatus::Pending->value);

        $sponsorRequests = $event->sponsorRequests()
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->paginate(50)
            ->withQueryString();

        return view('admin.sponsor-requests.index', [
            'event' => $event,
            'sponsorRequests' => $sponsorRequests,
            'status' => $status,
            'sponsorTiers' => $event->sponsorTiers,
        ]);
    }

    public function updateStatus(Event $event, SponsorRequest $sponsorRequest, string $status, Request $request): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $sponsorRequest);

        $validated = Validator::make(
            ['status' => $status, 'sponsor_tier_id' => $request->input('sponsor_tier_id')],
            [
                'status' => ['required', 'in:approved,rejected'],
                'sponsor_tier_id' => [
                    Rule::requiredIf($status === 'approved'),
                    'nullable',
                    Rule::exists('sponsor_tiers', 'id')->where('event_id', $event->id),
                ],
            ],
        )->validate();

        // Locked and re-checked so approving twice can't list the same sponsor twice, and an
        // already-decided request can't be flipped.
        $changed = DB::transaction(function () use ($event, $sponsorRequest, $validated): bool {
            $locked = SponsorRequest::query()->lockForUpdate()->findOrFail($sponsorRequest->id);

            if ($locked->status !== SponsorRequestStatus::Pending) {
                return false;
            }

            if ($validated['status'] === 'approved') {
                $event->sponsors()->create([
                    'name_ar' => $locked->name_ar,
                    'name_en' => $locked->name_en,
                    'logo_path' => $this->copyStoredMedia($locked->logo_path, 'sponsors'),
                    'sponsor_tier_id' => $validated['sponsor_tier_id'],
                    'website_url' => $locked->website_url,
                    'sort_order' => $event->sponsors()->max('sort_order') + 1,
                ]);
            }

            $locked->update([
                'status' => $validated['status'] === 'approved' ? SponsorRequestStatus::Approved : SponsorRequestStatus::Rejected,
            ]);

            return true;
        });

        $message = $validated['status'] === 'approved'
            ? __('Sponsor request approved and added to sponsors.')
            : __('Sponsor request rejected successfully.');

        return redirect()
            ->route('admin.events.sponsor-requests.index', $event)
            ->with($changed ? 'success' : 'error', $changed ? $message : __('This request has already been reviewed.'));
    }

    private function assertBelongsToEvent(Event $event, SponsorRequest $sponsorRequest): void
    {
        if ($sponsorRequest->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
