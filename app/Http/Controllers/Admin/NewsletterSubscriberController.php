<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\View\View;

class NewsletterSubscriberController extends Controller
{
    public function index(Event $event): View
    {
        return view('admin.newsletter-subscribers.index', ['event' => $event, 'newsletterSubscribers' => $event->newsletterSubscribers()->paginate(100)]);
    }
}
