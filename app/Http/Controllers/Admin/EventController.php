<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Models\Event;
use App\Support\UploadLimit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EventController extends Controller
{
    use HandlesMediaUploads;

    public function index(): View
    {
        return view('admin.events.index', ['events' => Event::orderBy('start_date')->get()]);
    }

    public function create(): View
    {
        return view('admin.events.form', ['event' => new Event, 'uploadLimit' => $this->uploadLimitLabel()]);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        $data = $this->withUploadedMedia($request->safe()->except('cover_image'), $request, 'cover_image', 'cover_image_path', 'events');

        Event::create($data);

        return redirect()->route('admin.events.index');
    }

    public function edit(Event $event): View
    {
        return view('admin.events.form', ['event' => $event, 'uploadLimit' => $this->uploadLimitLabel()]);
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $data = $this->withUploadedMedia($request->safe()->except('cover_image'), $request, 'cover_image', 'cover_image_path', 'events', $event->cover_image_path);

        $event->update($data);

        return redirect()->route('admin.events.index');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->deleteStoredMedia($event->cover_image_path);
        $event->delete();

        return redirect()->route('admin.events.index');
    }

    private function uploadLimitLabel(): string
    {
        return UploadLimit::label(UploadLimit::effectiveKilobytes((int) config('media.max_image_kb')));
    }
}
