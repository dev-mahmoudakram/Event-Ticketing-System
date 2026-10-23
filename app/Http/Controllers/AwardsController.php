<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LandingPageSection;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AwardsController extends Controller
{
    public function show(Request $request, Event $event): View
    {
        return view('awards.show', [
            'event' => $event,
            'blurb' => $event->contentFor(LandingPageSection::AwardsTeaser, 'blurb'),
            'votedAwardIds' => array_map('intval', (array) $request->session()->get('awarded.'.$event->id, [])),
        ]);
    }
}
