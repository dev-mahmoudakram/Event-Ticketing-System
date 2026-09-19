<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesMediaUploads;
use App\Http\Requests\SponsorRequestStoreRequest;
use App\Mail\SponsorRequestSubmitted;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SponsorRequestController extends Controller
{
    use HandlesMediaUploads;

    public function create(Event $event): View
    {
        return view('sponsor-requests.create', ['event' => $event]);
    }

    public function store(SponsorRequestStoreRequest $request, Event $event): RedirectResponse
    {
        $data = $request->safe()->except(['logo']);
        $data = $this->withUploadedMedia($data, $request, 'logo', 'logo_path', 'sponsor-requests');

        $sponsorRequest = $event->sponsorRequests()->create($data);

        try {
            Mail::to($sponsorRequest->email)->send(new SponsorRequestSubmitted($sponsorRequest));
        } catch (\Exception $e) {
            // The request itself already succeeded and is saved: a broken mail server should
            // not make the sponsor re-submit, so this is logged rather than surfaced to them.
            Log::error('Failed to send sponsor request submitted email.', [
                'sponsor_request_id' => $sponsorRequest->id,
                'exception' => $e,
            ]);
        }

        return redirect()->route('sponsor-requests.create', $event)->with('sponsor_request_success', true);
    }
}
