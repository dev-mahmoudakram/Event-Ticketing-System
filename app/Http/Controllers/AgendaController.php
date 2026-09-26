<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

class AgendaController extends Controller
{
    public function show(Event $event): View
    {
        $days = $event->agendaItems()->with(['speakers', 'sessionType', 'location'])->get()
            ->groupBy(fn ($item) => $item->day_date->toDateString())
            ->values();

        return view('agenda.show', ['event' => $event, 'days' => $days]);
    }
}
