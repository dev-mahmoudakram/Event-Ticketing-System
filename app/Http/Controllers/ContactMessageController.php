<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ContactMessageRequest;
use App\Models\ContactMessage;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;

class ContactMessageController extends Controller
{
    public function store(ContactMessageRequest $request, Event $event): RedirectResponse
    {
        $event->contactMessages()->create($request->validated());

        // The Contact page sends people back to itself; the landing page's section to its anchor.
        if ($request->input('return_to') === 'contact-page') {
            return redirect()->route('event-pages.show', [$event, 'contact'])->with('contact_success', true);
        }

        return redirect(route('landing.show', $event).'#contact')->with('contact_success', true);
    }

    public function storeGeneral(ContactMessageRequest $request): RedirectResponse
    {
        ContactMessage::create($request->validated());

        return redirect(route('home').'#contact')->with('contact_success', true);
    }
}
