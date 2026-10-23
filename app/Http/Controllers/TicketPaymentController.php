<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TicketPaymentStatus;
use App\Models\TicketPayment;
use App\Services\KashierGateway;
use App\Services\PaymentGatewayException;
use App\Services\TicketPaymentConfirmer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Where Kashier reports back: the customer's browser after checkout, and Kashier's server.
 *
 * Neither is trusted on its own word. Both must carry a valid Kashier signature, and even
 * then they only prompt TicketPaymentConfirmer to ask Kashier what really happened.
 */
class TicketPaymentController extends Controller
{
    /**
     * The page the customer lands on after checkout. It never redirects anywhere: whatever the
     * query string says, the page is ours and shows the payment as Kashier itself reports it.
     */
    public function return(Request $request, KashierGateway $gateway, TicketPaymentConfirmer $confirmer): View
    {
        abort_unless($gateway->hasValidRedirectSignature($request->query()), 403);

        $payment = TicketPayment::query()
            ->where('merchant_order_id', (string) $request->query('merchantOrderId'))
            ->firstOrFail();

        try {
            $confirmer->confirm($payment);
            $confirmer->rememberTransaction($payment, $request->query('transactionId'));
        } catch (PaymentGatewayException $exception) {
            // The webhook or the scheduled sync will settle it; the page says it is in hand.
            Log::warning('Could not confirm a payment on the customer\'s return.', [
                'ticket_payment_id' => $payment->id,
                'exception' => $exception,
            ]);
        }

        $payment->refresh();

        return view('tickets.payment-complete', [
            'payment' => $payment,
            'ticket' => $payment->ticket()->firstOrFail(),
            'reportedFailure' => strtoupper((string) $request->query('paymentStatus')) !== 'SUCCESS'
                && $payment->status === TicketPaymentStatus::Pending,
        ]);
    }

    /**
     * Kashier's server-to-server notice. 200 tells Kashier to stop; anything else is retried,
     * so a payment we could not confirm yet is answered with an error to be tried again.
     */
    public function webhook(Request $request, KashierGateway $gateway, TicketPaymentConfirmer $confirmer): Response
    {
        $data = $request->json('data');

        if (! is_array($data) || ! $gateway->hasValidWebhookSignature($data, $request->header('x-kashier-signature'))) {
            return response('Invalid signature.', 401);
        }

        $payment = TicketPayment::query()
            ->where('merchant_order_id', (string) ($data['merchantOrderId'] ?? ''))
            ->first();

        // Not one of ours (another integration on the same account); nothing to retry.
        if ($payment === null) {
            return response('Unknown order.', 200);
        }

        try {
            $confirmer->confirm($payment);
            $confirmer->rememberTransaction($payment, $data['transactionId'] ?? null);
        } catch (PaymentGatewayException $exception) {
            Log::warning('Could not confirm a payment from a Kashier webhook; Kashier will retry.', [
                'ticket_payment_id' => $payment->id,
                'exception' => $exception,
            ]);

            return response('Could not confirm yet.', 503);
        }

        return response('OK', 200);
    }
}
