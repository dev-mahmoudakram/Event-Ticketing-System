<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Mail\TicketRequestApproved;
use App\Mail\TicketRequestRejected;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketRequestAnswer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TicketRequestQueueController extends Controller
{
    public function index(Event $event, Request $request): View
    {
        $status = $request->query('status', TicketStatus::Pending->value);

        $tickets = $event->tickets()
            ->with(['ticketType', 'answers.field'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->get();

        return view('admin.ticket-requests.index', ['event' => $event, 'tickets' => $tickets, 'status' => $status]);
    }

    public function updateStatus(Event $event, Ticket $ticket, string $status): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $ticket);

        $status = Validator::make(
            ['status' => $status],
            ['status' => ['required', 'in:approved,rejected']],
        )->validate()['status'];

        $ticketStatus = $status === 'approved' ? TicketStatus::PaymentPending : TicketStatus::Rejected;
        $mail = $status === 'approved'
            ? new TicketRequestApproved($ticket, URL::temporarySignedRoute('tickets.payment', now()->addDays(7), ['ticket' => $ticket]))
            : new TicketRequestRejected($ticket);
        $successMessage = $status === 'approved'
            ? __('Ticket approved successfully.')
            : __('Ticket rejected successfully.');

        try {
            Mail::to($ticket->email)->send($mail);
            $ticket->update(['status' => $ticketStatus]);

            return redirect()
                ->route('admin.events.ticket-requests.index', $event)
                ->with('success', $successMessage);
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
