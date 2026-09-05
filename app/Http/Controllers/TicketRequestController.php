<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TicketRequestFieldType;
use App\Enums\TicketStatus;
use App\Http\Requests\TicketRequestStoreRequest;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketRequestField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketRequestController extends Controller
{
    public function store(TicketRequestStoreRequest $request, Event $event): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $fields = $event->ticketRequestFields;

        $ticket = DB::transaction(function () use ($validated, $event, $fields, $request) {
            $ticket = $event->tickets()->create([
                'ticket_type_id' => $validated['ticket_type_id'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'status' => TicketStatus::Pending,
            ]);

            $ticket->update(['ticket_number' => $this->generateTicketNumber($event, $ticket)]);

            foreach ($fields as $field) {
                $this->storeAnswerFor($ticket, $field, $request);
            }

            return $ticket;
        });

        $message = __('Request received! Your reference number is :number.', ['number' => $ticket->ticket_number]);

        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'reference' => $ticket->ticket_number]);
        }

        return redirect()->to(route('landing.show', $event).'#tickets')->with('ticket_request_success', $ticket->ticket_number);
    }

    private function storeAnswerFor(Ticket $ticket, TicketRequestField $field, Request $request): void
    {
        $inputKey = 'field_'.$field->id;

        if ($field->type === TicketRequestFieldType::Portfolio) {
            $mode = $request->input($inputKey.'_mode');

            if ($mode === 'pdf' && $request->hasFile($inputKey.'_file')) {
                $path = $request->file($inputKey.'_file')->store('ticket-uploads/'.$ticket->id, 'local');
                $ticket->answers()->create(['ticket_request_field_id' => $field->id, 'file_path' => $path]);
            } elseif ($mode === 'url' && $request->filled($inputKey.'_url')) {
                $ticket->answers()->create(['ticket_request_field_id' => $field->id, 'value' => $request->input($inputKey.'_url')]);
            }

            return;
        }

        if ($field->type === TicketRequestFieldType::Cv) {
            if ($request->hasFile($inputKey)) {
                $path = $request->file($inputKey)->store('ticket-uploads/'.$ticket->id, 'local');
                $ticket->answers()->create(['ticket_request_field_id' => $field->id, 'file_path' => $path]);
            }

            return;
        }

        if ($request->filled($inputKey)) {
            $ticket->answers()->create(['ticket_request_field_id' => $field->id, 'value' => $request->input($inputKey)]);
        }
    }

    private function generateTicketNumber(Event $event, Ticket $ticket): string
    {
        $prefix = strtoupper(str_replace('-', '', $event->slug));

        return $prefix.'-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT);
    }
}
