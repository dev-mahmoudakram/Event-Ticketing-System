<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LandingPageSection;
use App\Enums\VotingMode;
use App\Models\Event;
use App\Services\AwardVoting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AwardsController extends Controller
{
    private const STATUS_DATE_FORMAT = 'j F Y, g:i A';

    public function show(Request $request, Event $event, AwardVoting $voting): View
    {
        $event->load(['awards.nominees']);

        return view('awards.show', [
            'event' => $event,
            'blurb' => $event->contentFor(LandingPageSection::AwardsTeaser, 'blurb'),
            'votedAwardIds' => array_map('intval', (array) $request->session()->get('awarded.'.$event->id, [])),
            'awards' => $event->awards,
            'counts' => $voting->counts($event),
            'votingIsOpen' => $event->votingIsOpen(),
            'statusLine' => $this->statusLine($event),
            'turnstileSiteKey' => (string) config('services.turnstile.site_key'),
        ]);
    }

    /**
     * The one line under the heading that tells a visitor where voting stands.
     */
    private function statusLine(Event $event): string
    {
        if ($event->votingIsOpen()) {
            if ($event->voting_mode !== VotingMode::Open && $event->voting_closes_at !== null) {
                return __('Voting closes on :date', ['date' => $event->voting_closes_at->translatedFormat(self::STATUS_DATE_FORMAT)]);
            }

            return __('Voting is open');
        }

        if ($event->voting_mode === VotingMode::Schedule && $event->voting_opens_at?->isFuture()) {
            return __('Voting opens on :date', ['date' => $event->voting_opens_at->translatedFormat(self::STATUS_DATE_FORMAT)]);
        }

        return __('Voting is closed');
    }
}
