<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SponsorRequest;
use App\Models\Event;
use App\Models\Sponsor;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SponsorController extends Controller
{
    use HandlesMediaUploads;

    public function index(Event $event): View
    {
        return view('admin.sponsors.index', ['event' => $event, 'sponsors' => $event->sponsors()->with('tier')->get()]);
    }

    public function create(Event $event): View
    {
        return view('admin.sponsors.form', ['event' => $event, 'sponsor' => new Sponsor, 'sponsorTiers' => $event->sponsorTiers]);
    }

    public function store(SponsorRequest $request, Event $event): RedirectResponse
    {
        $data = $request->safe()->except(['logo']);
        $data = $this->withUploadedMedia($data, $request, 'logo', 'logo_path', 'sponsors');

        $event->sponsors()->create($data);

        return redirect()->route('admin.events.sponsors.index', $event);
    }

    public function edit(Event $event, Sponsor $sponsor): View
    {
        $this->assertBelongsToEvent($event, $sponsor);

        return view('admin.sponsors.form', ['event' => $event, 'sponsor' => $sponsor, 'sponsorTiers' => $event->sponsorTiers]);
    }

    public function update(SponsorRequest $request, Event $event, Sponsor $sponsor): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $sponsor);
        $data = $request->safe()->except(['logo']);
        $data = $this->withUploadedMedia($data, $request, 'logo', 'logo_path', 'sponsors', $sponsor->logo_path);

        $sponsor->update($data);

        return redirect()->route('admin.events.sponsors.index', $event);
    }

    public function destroy(Event $event, Sponsor $sponsor): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $sponsor);
        $this->deleteStoredMedia($sponsor->logo_path);
        $sponsor->delete();

        return redirect()->route('admin.events.sponsors.index', $event);
    }

    private function assertBelongsToEvent(Event $event, Sponsor $sponsor): void
    {
        if ($sponsor->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
