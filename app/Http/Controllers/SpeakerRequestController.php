<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesMediaUploads;
use App\Http\Requests\SpeakerRequestStoreRequest;
use App\Mail\SpeakerRequestSubmitted;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SpeakerRequestController extends Controller
{
    use HandlesMediaUploads;

    public function create(Event $event): View
    {
        return view('speaker-requests.create', ['event' => $event]);
    }

    public function store(SpeakerRequestStoreRequest $request, Event $event): RedirectResponse
    {
        $data = $request->safe()->except(['photo']);
        $data = $this->withUploadedMedia($data, $request, 'photo', 'photo_path', 'speaker-requests');

        $speakerRequest = $event->speakerRequests()->create($data);

        try {
            Mail::to($speakerRequest->email)->send(new SpeakerRequestSubmitted($speakerRequest));
        } catch (\Exception $e) {
            // The request itself already succeeded and is saved: a broken mail server should
            // not make the speaker re-submit, so this is logged rather than surfaced to them.
            Log::error('Failed to send speaker request submitted email.', [
                'speaker_request_id' => $speakerRequest->id,
                'exception' => $e,
            ]);
        }

        return redirect()->route('speaker-requests.create', $event)->with('speaker_request_success', true);
    }
}
