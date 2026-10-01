<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TicketRequestFieldType;
use App\Enums\TicketStatus;
use App\Http\Requests\TicketRequestStoreRequest;
use App\Mail\TicketRequestReceived;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketRequestField;
use App\Models\TicketType;
use App\Services\CouponRedeemer;
use App\Services\TicketIssuer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TicketRequestController extends Controller
{
    private const REQUESTS_PER_EMAIL_PER_DAY = 3;

    public function __construct(
        private readonly CouponRedeemer $coupons,
        private readonly TicketIssuer $issuer,
    ) {}

    public function store(TicketRequestStoreRequest $request, Event $event): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $fields = $event->ticketRequestFields;

        // Every request emails the address typed in, so one address is capped per event per
        // day: otherwise the form could be used to mail a stranger over and over. Only requests
        // that go through count, so fixing a typo doesn't use up an attempt.
        $emailKey = 'ticket-request-email|'.$event->id.'|'.Str::lower(trim($validated['email']));

        if (RateLimiter::tooManyAttempts($emailKey, self::REQUESTS_PER_EMAIL_PER_DAY)) {
            throw ValidationException::withMessages([
                'email' => __('This email has already sent the maximum number of ticket requests today. Please try again tomorrow.'),
            ]);
        }

        $ticket = DB::transaction(function () use ($validated, $event, $fields, $request) {
            // The price is settled here and copied onto the ticket: a coupon that expires, or a
            // ticket type whose price changes, must not rewrite what this attendee was quoted.
            $ticketType = TicketType::findOrFail($validated['ticket_type_id']);
            $coupon = $this->coupons->find($event, $validated['coupon_code'] ?? null);

            if ($coupon !== null && ! $this->coupons->claim($coupon)) {
                $coupon = null;
            }

            $pricing = $this->coupons->priceFor($ticketType, $coupon);

            $isOtherCategory = ($validated['influencer_category_id'] ?? null) === 'other';

            $ticket = $event->tickets()->create([
                'ticket_type_id' => $validated['ticket_type_id'],
                'influencer_category_id' => $isOtherCategory ? null : ($validated['influencer_category_id'] ?? null),
                'influencer_category_other' => $isOtherCategory ? $validated['influencer_category_other'] : null,
                'discount_coupon_id' => $pricing['coupon_id'],
                'price' => $pricing['price'],
                'discount_amount' => $pricing['discount'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'status' => TicketStatus::Pending,
                'terms_accepted_at' => now(),
            ]);

            $ticket->update(['ticket_number' => $this->issuer->referenceFor($ticket)]);

            foreach ($fields as $field) {
                $this->storeAnswerFor($ticket, $field, $request);
            }

            return $ticket;
        });

        RateLimiter::hit($emailKey, 60 * 60 * 24);

        try {
            Mail::to($ticket->email)->send(new TicketRequestReceived($ticket));
        } catch (\Exception $e) {
            // The request itself already succeeded and is saved: a broken mail server should
            // not make the attendee re-submit, so this is logged rather than surfaced to them.
            Log::error('Failed to send ticket request received email.', [
                'ticket_id' => $ticket->id,
                'exception' => $e,
            ]);
        }

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
            $supportsFollowerCount = in_array($field->type, [TicketRequestFieldType::Instagram, TicketRequestFieldType::SocialLink], true);

            $ticket->answers()->create([
                'ticket_request_field_id' => $field->id,
                'value' => $request->input($inputKey),
                'follower_count' => $supportsFollowerCount ? $request->input($inputKey.'_followers') : null,
            ]);
        }
    }
}
