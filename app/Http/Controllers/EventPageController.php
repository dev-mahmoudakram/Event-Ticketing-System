<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

/**
 * An event's published pages: its policies, About and Contact, and any custom pages.
 */
class EventPageController extends Controller
{
    public function show(Event $event, string $slug): View
    {
        $page = $event->pages()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('event-pages.show', ['event' => $event, 'page' => $page]);
    }
}
