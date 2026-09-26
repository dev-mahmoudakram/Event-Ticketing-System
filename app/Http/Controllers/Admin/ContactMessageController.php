<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function index(Event $event): View
    {
        return view('admin.contact-messages.index', ['event' => $event, 'contactMessages' => $event->contactMessages()->paginate(50)]);
    }
}
