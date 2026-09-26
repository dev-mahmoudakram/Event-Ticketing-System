<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventSchedule;
use Illuminate\View\View;

class AgendaController extends Controller
{
    public function show(Event $event, EventSchedule $schedule): View
    {
        return view('agenda.show', ['event' => $event, 'days' => $schedule->forEvent($event)->values()]);
    }
}
