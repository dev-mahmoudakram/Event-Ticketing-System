<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\InvitationRequestStatus;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Mail\InvitationRequestRejected;
use App\Models\Event;
use App\Models\InvitationRequest;
use App\Services\TicketIssuer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class InvitationRequestController extends Controller
{
    public function __construct(private readonly TicketIssuer $issuer) {}

    public function index(Event $event, Request $request): View
    {
        $status = $request->query('status', InvitationRequestStatus::Pending->value);

        $invitationRequests = $event->invitationRequests()
            ->with(['invitation.ticketType', 'influencerCategory'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->paginate(50)
            ->withQueryString();

        return view('admin.invitation-requests.index', compact('event', 'invitationRequests', 'status'));
    }

    public function updateStatus(Event $event, InvitationRequest $invitationRequest, string $status): RedirectResponse
    {
        if ($invitationRequest->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }

        $status = Validator::make(['status' => $status], [
            'status' => ['required', 'in:approved,rejected'],
        ])->validate()['status'];

        try {
            $changed = DB::transaction(function () use ($event, $invitationRequest, $status): bool {
                $request = InvitationRequest::query()->lockForUpdate()->findOrFail($invitationRequest->id);

                if ($request->status !== InvitationRequestStatus::Pending) {
                    return false;
                }

                if ($status === 'rejected') {
                    Mail::to($request->email)->send(new InvitationRequestRejected($request));
                    $request->update(['status' => InvitationRequestStatus::Rejected]);

                    return true;
                }

                $invitation = $request->invitation()->firstOrFail();
                $ticket = $event->tickets()->create([
                    'ticket_type_id' => $invitation->ticket_type_id,
                    'influencer_category_id' => $request->influencer_category_id,
                    'influencer_category_other' => $request->influencer_category_other,
                    // An invited guest pays nothing, so the ticket is recorded at zero: every
                    // revenue report sums price over paid tickets.
                    'price' => 0,
                    'discount_amount' => 0,
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'status' => TicketStatus::Approved,
                ]);
                $ticket->update(['ticket_number' => $this->issuer->referenceFor($ticket)]);
                $this->issuer->issue($ticket, 'invitation', TicketStatus::Approved);

                // Sent before the transaction commits on purpose: if the email fails, nothing is
                // saved and the admin sees an error and can simply approve again.
                $this->issuer->send($ticket);
                $request->update(['ticket_id' => $ticket->id, 'status' => InvitationRequestStatus::Approved]);

                return true;
            });

            $message = $status === 'approved' ? __('Request approved and ticket issued.') : __('Request rejected successfully.');

            return redirect()->route('admin.events.invitation-requests.index', $event)
                ->with($changed ? 'success' : 'error', $changed ? $message : __('This request has already been reviewed.'));
        } catch (Throwable $exception) {
            Log::error('Failed to review invitation request.', [
                'invitation_request_id' => $invitationRequest->id,
                'status' => $status,
                'exception' => $exception,
            ]);

            return redirect()->route('admin.events.invitation-requests.index', $event)
                ->with('error', __('The request was not changed because it could not be completed.'));
        }
    }
}
