<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SponsorTierRequest;
use App\Models\Event;
use App\Models\SponsorTier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SponsorTierController extends Controller
{
    public function index(Event $event): View
    {
        return view('admin.sponsor-tiers.index', ['event' => $event, 'sponsorTiers' => $event->sponsorTiers]);
    }

    public function create(Event $event): View
    {
        return view('admin.sponsor-tiers.form', ['event' => $event, 'sponsorTier' => new SponsorTier]);
    }

    public function store(SponsorTierRequest $request, Event $event): RedirectResponse
    {
        $event->sponsorTiers()->create($request->validated());

        return redirect()->route('admin.events.sponsor-tiers.index', $event);
    }

    public function edit(Event $event, SponsorTier $sponsorTier): View
    {
        $this->assertBelongsToEvent($event, $sponsorTier);

        return view('admin.sponsor-tiers.form', ['event' => $event, 'sponsorTier' => $sponsorTier]);
    }

    public function update(SponsorTierRequest $request, Event $event, SponsorTier $sponsorTier): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $sponsorTier);
        $sponsorTier->update($request->validated());

        return redirect()->route('admin.events.sponsor-tiers.index', $event);
    }

    public function destroy(Event $event, SponsorTier $sponsorTier): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $sponsorTier);
        $sponsorTier->delete();

        return redirect()->route('admin.events.sponsor-tiers.index', $event);
    }

    private function assertBelongsToEvent(Event $event, SponsorTier $sponsorTier): void
    {
        if ($sponsorTier->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
