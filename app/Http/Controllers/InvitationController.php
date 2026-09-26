<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function show(Event $event, string $token): View
    {
        return view('invitations.verify', [
            'event' => $event,
            'token' => $token,
            'invalid' => $this->findUsable($event, $token) === null,
        ]);
    }

    public function verify(Request $request, Event $event, string $token): RedirectResponse|View
    {
        $invitation = $this->findUsable($event, $token);

        if ($invitation === null) {
            return view('invitations.verify', ['event' => $event, 'token' => $token, 'invalid' => true]);
        }

        $validated = $request->validate(['otp' => ['required', 'digits:6']]);

        if (! hash_equals($invitation->otp, $validated['otp'])) {
            return back()->withErrors(['otp' => __('That code is not correct.')]);
        }

        // A fresh session ID once the code is proven, so an ID planted before verification
        // can't ride along into the invitee's form.
        $request->session()->regenerate();
        $request->session()->put('invitation_verified.'.$event->id, $invitation->id);

        return redirect()->route('invitations.create', [$event, $token]);
    }

    private function findUsable(Event $event, string $token): ?Invitation
    {
        $invitation = $event->invitations()->where('token', $token)->first();

        return $invitation !== null && $invitation->isUsable() ? $invitation : null;
    }
}
