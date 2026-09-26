<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Workshop;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WorkshopController extends Controller
{
    public function index(Event $event): View
    {
        return view('workshops.index', [
            'event' => $event,
            'workshops' => $event->workshops()->with('speakers')->get(),
        ]);
    }

    public function show(Event $event, Workshop $workshop): View
    {
        if ($workshop->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }

        $workshop->load(['speakers', 'location']);

        return view('workshops.show', ['event' => $event, 'workshop' => $workshop]);
    }
}
