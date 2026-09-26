<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InvitationStoreRequest;
use App\Models\Event;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class InvitationController extends Controller
{
    public function index(Event $event): View
    {
        return view('admin.invitations.index', [
            'event' => $event,
            'invitations' => $event->invitations()->with('ticketType')->paginate(50),
            'ticketTypes' => $event->ticketTypes()->where('is_active', true)->get(),
        ]);
    }

    public function store(InvitationStoreRequest $request, Event $event): RedirectResponse
    {
        $invitation = $event->invitations()->create([
            'ticket_type_id' => $request->validated('ticket_type_id'),
            'token' => Str::random(40),
            'otp' => (string) random_int(100000, 999999),
            'status' => InvitationStatus::Unused,
            'expires_at' => now()->addDays(7),
        ]);

        return redirect()->route('admin.events.invitations.index', $event)
            ->with('generated_invitation_id', $invitation->id);
    }

    public function revoke(Event $event, Invitation $invitation): RedirectResponse
    {
        if ($invitation->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }

        Invitation::query()->whereKey($invitation->id)
            ->where('status', InvitationStatus::Unused->value)
            ->update(['status' => InvitationStatus::Revoked]);

        return redirect()->route('admin.events.invitations.index', $event);
    }
}
