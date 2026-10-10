<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Mail\TicketRequestRejected;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketRequestAnswer;
use App\Services\PaymentGatewayException;
use App\Services\PaymentLinkSender;
use App\Services\TicketPaymentConfirmer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TicketRequestQueueController extends Controller
{
    public function __construct(
        private readonly PaymentLinkSender $paymentLinks,
        private readonly TicketPaymentConfirmer $paymentConfirmer,
    ) {}

    public function index(Event $event, Request $request): View
    {
        $status = $request->query('status', TicketStatus::Pending->value);

        $tickets = $event->tickets()
            ->with(['ticketType', 'influencerCategory', 'answers.field', 'latestPayment'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.ticket-requests.index', ['event' => $event, 'tickets' => $tickets, 'status' => $status]);
    }

    public function updateStatus(Event $event, Ticket $ticket, string $status, Request $request): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $ticket);

        $status = Validator::make(
            ['status' => $status],
            ['status' => ['required', 'in:approved,rejected']],
        )->validate()['status'];

        $successMessage = $status === 'approved'
            ? __('Ticket approved successfully.')
            : __('Ticket rejected successfully.');

        try {
            // Locked and re-checked so a double click, a stale tab or a second admin can't
            // re-review a ticket — re-approving an issued one would demote it to
            // payment_pending, and the attendee would then be refused at check-in.
            // Approving opens a Kashier payment link and emails it; if either fails, the whole
            // review rolls back and the request stays pending.
            $changed = DB::transaction(function () use ($ticket, $status, $request): bool {
                $locked = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);

                if ($locked->status !== TicketStatus::Pending) {
                    return false;
                }

                if ($status === 'approved') {
                    $locked->update(['status' => TicketStatus::PaymentPending]);
                    $this->paymentLinks->send($locked, $request->user());
                } else {
                    Mail::to($locked->email)->send(new TicketRequestRejected($locked));
                    $locked->update(['status' => TicketStatus::Rejected]);
                }

                return true;
            });

            return redirect()
                ->route('admin.events.ticket-requests.index', $event)
                ->with($changed ? 'success' : 'error', $changed ? $successMessage : __('This request has already been reviewed.'));
        } catch (PaymentGatewayException $e) {
            Log::error('Failed to create a Kashier payment link on approval.', [
                'ticket_id' => $ticket->id,
                'exception' => $e,
            ]);

            return redirect()
                ->route('admin.events.ticket-requests.index', $event)
                ->with('error', __('The ticket was not approved because the payment link could not be created. Check the payment settings and try again.'));
        } catch (\Exception $e) {
            Log::error('Failed to send ticket approval email.', [
                'ticket_id' => $ticket->id,
                'status' => $status,
                'exception' => $e,
            ]);

            return redirect()
                ->route('admin.events.ticket-requests.index', $event)
                ->with('error', __('The ticket status was not changed because the email could not be sent.'));
        }
    }

    /**
     * Email an approved attendee a new payment link, closing the old one.
     *
     * Open links are checked with Kashier first: if the attendee has in fact paid, the ticket
     * is issued from that payment rather than sent a second link to pay again.
     */
    public function resendPaymentLink(Event $event, Ticket $ticket, Request $request): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $ticket);

        $back = redirect()->route('admin.events.ticket-requests.index', [$event, 'status' => TicketStatus::PaymentPending->value]);

        try {
            $this->paymentConfirmer->confirmOpenPayments($ticket);

            $sent = DB::transaction(function () use ($ticket, $request): bool {
                $locked = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);

                if ($locked->status !== TicketStatus::PaymentPending) {
                    return false;
                }

                $this->paymentLinks->send($locked, $request->user());

                return true;
            });
        } catch (PaymentGatewayException $e) {
            Log::error('Failed to resend a Kashier payment link.', ['ticket_id' => $ticket->id, 'exception' => $e]);

            return $back->with('error', __('The payment link could not be created. Check the payment settings and try again.'));
        } catch (\Exception $e) {
            Log::error('Failed to email a new payment link.', ['ticket_id' => $ticket->id, 'exception' => $e]);

            return $back->with('error', __('The new payment link was not sent because the email could not be sent.'));
        }

        return $back->with(
            $sent ? 'success' : 'error',
            $sent ? __('A new payment link was emailed to :email.', ['email' => $ticket->email]) : __('This ticket is no longer awaiting payment.'),
        );
    }

    public function downloadAnswer(Event $event, Ticket $ticket, TicketRequestAnswer $answer): StreamedResponse
    {
        $this->assertBelongsToEvent($event, $ticket);

        if ($answer->ticket_id !== $ticket->id || ! $answer->file_path) {
            throw new NotFoundHttpException;
        }

        return Storage::disk('local')->download($answer->file_path);
    }

    private function assertBelongsToEvent(Event $event, Ticket $ticket): void
    {
        if ($ticket->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
