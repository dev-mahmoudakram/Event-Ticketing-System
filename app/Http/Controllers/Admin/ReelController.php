<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReelRequest;
use App\Models\Event;
use App\Models\Reel;
use App\Support\UploadLimit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReelController extends Controller
{
    use HandlesMediaUploads;

    public function index(Event $event): View
    {
        return view('admin.reels.index', ['event' => $event, 'reels' => $event->reels]);
    }

    public function create(Event $event): View
    {
        return view('admin.reels.form', ['event' => $event, 'reel' => new Reel, 'uploadLimit' => $this->uploadLimitNotice()]);
    }

    public function store(ReelRequest $request, Event $event): RedirectResponse
    {
        $data = $request->safe()->except(['video', 'poster']);
        $data = $this->withUploadedMedia($data, $request, 'video', 'video_path', 'reels');
        $data = $this->withUploadedMedia($data, $request, 'poster', 'poster_path', 'reels/posters');

        $event->reels()->create($data);

        return redirect()->route('admin.events.reels.index', $event);
    }

    public function edit(Event $event, Reel $reel): View
    {
        $this->assertBelongsToEvent($event, $reel);

        return view('admin.reels.form', ['event' => $event, 'reel' => $reel, 'uploadLimit' => $this->uploadLimitNotice()]);
    }

    public function update(ReelRequest $request, Event $event, Reel $reel): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $reel);

        $data = $request->safe()->except(['video', 'poster']);
        $data = $this->withUploadedMedia($data, $request, 'video', 'video_path', 'reels', $reel->video_path);
        $data = $this->withUploadedMedia($data, $request, 'poster', 'poster_path', 'reels/posters', $reel->poster_path);

        $reel->update($data);

        return redirect()->route('admin.events.reels.index', $event);
    }

    public function destroy(Event $event, Reel $reel): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $reel);

        $this->deleteStoredMedia($reel->video_path);
        $this->deleteStoredMedia($reel->poster_path);
        $reel->delete();

        return redirect()->route('admin.events.reels.index', $event);
    }

    /**
     * Text for the upload area: the intended ceiling, plus a nudge about the server's own
     * limit when that is the lower of the two.
     */
    private function uploadLimitNotice(): string
    {
        $intended = (int) config('media.max_video_kb');

        if (UploadLimit::isServerConstrained($intended)) {
            return __('Vertical clip (9:16 works best). Up to :intended — but this server currently accepts only :server.', [
                'intended' => UploadLimit::label($intended),
                'server' => UploadLimit::label(UploadLimit::serverKilobytes()),
            ]);
        }

        return __('Vertical clip (9:16 works best). Up to :intended.', [
            'intended' => UploadLimit::label($intended),
        ]);
    }

    private function assertBelongsToEvent(Event $event, Reel $reel): void
    {
        if ($reel->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
