<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

class SpeakerController extends Controller
{
    public function index(Event $event): View
    {
        return view('speakers.index', [
            'event' => $event,
            'speakers' => $event->speakers,
        ]);
    }
}
