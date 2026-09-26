<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\InvitationStatus;
use App\Http\Requests\InvitationRequestStoreRequest;
use App\Mail\InvitationRequestReceived;
use App\Models\Event;
use App\Models\Invitation;
use App\Models\InvitationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class InvitationRequestController extends Controller
{
    public function create(Request $request, Event $event, string $token): RedirectResponse|View
    {
        if ($this->verifiedInvitation($request, $event, $token) === null) {
            return redirect()->route('invitations.verify', [$event, $token]);
        }

        return view('invitations.create', ['event' => $event, 'token' => $token]);
    }

    public function store(InvitationRequestStoreRequest $request, Event $event, string $token): RedirectResponse
    {
        $invitation = $this->verifiedInvitation($request, $event, $token);

        if ($invitation === null) {
            return redirect()->route('invitations.verify', [$event, $token]);
        }

        $validated = $request->validated();
        $created = DB::transaction(function () use ($event, $invitation, $validated): ?InvitationRequest {
            $lockedInvitation = Invitation::query()->lockForUpdate()->findOrFail($invitation->id);

            if (! $lockedInvitation->isUsable()) {
                return null;
            }

            $isOtherCategory = ($validated['influencer_category_id'] ?? null) === 'other';

            $created = $event->invitationRequests()->create([
                'invitation_id' => $lockedInvitation->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'influencer_category_id' => $isOtherCategory ? null : ($validated['influencer_category_id'] ?? null),
                'influencer_category_other' => $isOtherCategory ? $validated['influencer_category_other'] : null,
                'instagram_url' => $validated['instagram_url'] ?? null,
                'instagram_followers' => $validated['instagram_followers'] ?? null,
                'facebook_url' => $validated['facebook_url'] ?? null,
                'facebook_followers' => $validated['facebook_followers'] ?? null,
                'tiktok_url' => $validated['tiktok_url'] ?? null,
                'tiktok_followers' => $validated['tiktok_followers'] ?? null,
            ]);

            $lockedInvitation->update(['status' => InvitationStatus::Used]);

            return $created;
        });

        if ($created === null) {
            return redirect()->route('invitations.verify', [$event, $token]);
        }

        $request->session()->forget('invitation_verified.'.$event->id);

        try {
            Mail::to($created->email)->send(new InvitationRequestReceived($created));
        } catch (\Exception $exception) {
            Log::error('Failed to send invitation request received email.', [
                'invitation_request_id' => $created->id,
                'exception' => $exception,
            ]);
        }

        return redirect()->route('landing.show', $event)->with('invitation_request_success', true);
    }

    private function verifiedInvitation(Request $request, Event $event, string $token): ?Invitation
    {
        $verifiedId = $request->session()->get('invitation_verified.'.$event->id);

        if ($verifiedId === null) {
            return null;
        }

        $invitation = $event->invitations()->where('token', $token)->first();

        return $invitation !== null && $invitation->id === $verifiedId && $invitation->isUsable()
            ? $invitation
            : null;
    }
}
