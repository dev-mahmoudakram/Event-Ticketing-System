<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function show(Event $event): View
    {
        $event->load([
            'speakers', 'sponsors', 'ticketTypes.features', 'ticketRequestFields',
            'workshops' => fn ($query) => $query->withCount('bookings'), 'workshops.speakers', 'workshops.location',
            'faqs', 'landingPageContent', 'galleryPhotos', 'testimonials', 'reels',
        ]);

        return view('landing.show', ['event' => $event]);
    }
}
