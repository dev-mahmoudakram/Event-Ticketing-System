<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketPaymentStatus;
use App\Enums\TicketStatus;
use App\Mail\TicketIssued;
use App\Models\Event;
use App\Models\PaymentSetting;
use App\Models\Ticket;
use App\Models\TicketPayment;
use App\Models\TicketType;
use App\Services\TicketIssuer;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

/**
 * Kashier telling us a payment link was paid — through the customer's return, the webhook and
 * the scheduled sync — and the ticket being issued exactly once, only when Kashier confirms it.
 */
class TicketPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const API_KEY = 'test-payment-api-key';

    protected function setUp(): void
    {
        parent::setUp();

        PaymentSetting::create([
            'mode' => 'test',
            'merchant_id' => 'MID-1234-567',
            'api_key' => self::API_KEY,
            'secret_key' => 'test-secret-key',
        ]);
    }

    private function openPayment(int $workshopSlots = 0): TicketPayment
    {
        $event = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create(['workshop_slot_count' => $workshopSlots, 'price' => 1500]);
        $ticket = Ticket::factory()->for($event)->for($ticketType)->create([
            'status' => TicketStatus::PaymentPending,
            'email' => 'attendee@example.com',
            'price' => 1500,
            'discount_amount' => 0,
            'ticket_id' => null,
            'is_paid' => false,
        ]);

        return TicketPayment::factory()->for($ticket)->create([
            'merchant_order_id' => 'CCS-000001-ABC123',
            'session_id' => 'session123',
            'amount' => 1500,
            'currency' => 'EGP',
        ]);
    }

    /**
     * Kashier's answer when asked about the session.
     *
     * @param  array<string, string>  $overrides
     */
    private function fakeKashierSession(string $status, array $overrides = []): void
    {
        Http::fake([
            'test-api.kashier.io/v3/payment/sessions/session123/payment' => Http::response([
                'message' => 'success',
                'data' => array_merge([
                    'sessionId' => 'session123',
                    'status' => $status,
                    'merchantOrderId' => 'CCS-000001-ABC123',
                    'amount' => '1500.00',
                    'currency' => 'EGP',
                ], $overrides),
            ]),
        ]);
    }

    /**
     * The query string Kashier sends the customer back with, signed the way Kashier signs it.
     *
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function signedRedirect(string $paymentStatus = 'SUCCESS', array $overrides = []): array
    {
        $query = array_merge([
            'paymentStatus' => $paymentStatus,
            'merchantOrderId' => 'CCS-000001-ABC123',
            'orderId' => 'kashier-order-1',
            'orderReference' => 'TEST-ORD-1',
            'transactionId' => 'TX-1001',
            'amount' => '1500',
            'currency' => 'EGP',
            'mode' => 'test',
        ], $overrides);

        $fields = ['paymentStatus', 'cardDataToken', 'maskedCard', 'merchantOrderId', 'orderId', 'cardBrand', 'orderReference', 'transactionId', 'amount', 'currency'];
        $payload = implode('&', array_map(fn (string $field) => $field.'='.($query[$field] ?? 'null'), $fields));
        $query['signature'] = hash_hmac('sha256', $payload, self::API_KEY);

        return $query;
    }

    /**
     * A webhook body and its signature header, signed the way Kashier signs it.
     *
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function signedWebhook(string $status = 'SUCCESS'): array
    {
        $data = [
            'merchantOrderId' => 'CCS-000001-ABC123',
            'kashierOrderId' => 'kashier-order-1',
            'orderReference' => 'TEST-ORD-1',
            'transactionId' => 'TX-1001',
            'status' => $status,
            'method' => 'card',
            'amount' => 1500,
            'currency' => 'EGP',
            'channel' => 'online | e-commerce',
            'transactionResponseCode' => '00',
            'signatureKeys' => ['amount', 'channel', 'currency', 'kashierOrderId', 'merchantOrderId', 'method', 'orderReference', 'status', 'transactionId', 'transactionResponseCode'],
        ];

        $keys = $data['signatureKeys'];
        sort($keys);
        $signed = array_intersect_key($data, array_flip($keys));
        ksort($signed);

        return [['event' => 'pay', 'data' => $data], hash_hmac('sha256', http_build_query($signed, '', '&', PHP_QUERY_RFC3986), self::API_KEY)];
    }

    public function test_returning_from_a_paid_checkout_issues_the_ticket_and_emails_it(): void
    {
        Mail::fake();
        $payment = $this->openPayment(workshopSlots: 1);
        $this->fakeKashierSession('PAID');

        $response = $this->get(route('payments.kashier.return', $this->signedRedirect()));

        $response->assertOk();
        $ticket = $payment->ticket->fresh();
        $this->assertSame(TicketStatus::TicketIssued, $ticket->status);
        $this->assertTrue($ticket->is_paid);
        $this->assertSame('kashier', $ticket->payment_method);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{40}$/', $ticket->ticket_id);
        $this->assertNotNull($ticket->workshop_booking_key);
        $this->assertSame(TicketPaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame('TX-1001', $payment->fresh()->transaction_id);
        $response->assertSee(route('tickets.show', [$ticket, $ticket->ticket_id]), false);
        Mail::assertSent(TicketIssued::class, fn (TicketIssued $mail) => $mail->hasTo('attendee@example.com'));
    }

    public function test_a_tier_without_workshops_gets_no_booking_key(): void
    {
        Mail::fake();
        $payment = $this->openPayment(workshopSlots: 0);
        $this->fakeKashierSession('PAID');

        $this->get(route('payments.kashier.return', $this->signedRedirect()))->assertOk();

        $this->assertNull($payment->ticket->fresh()->workshop_booking_key);
    }

    public function test_the_return_page_trusts_kashier_not_the_query_string(): void
    {
        Mail::fake();
        $payment = $this->openPayment();
        // The redirect says SUCCESS, but Kashier itself has not seen the money.
        $this->fakeKashierSession('PENDING');

        $this->get(route('payments.kashier.return', $this->signedRedirect('SUCCESS')))
            ->assertOk()
            ->assertSee(__('We are confirming your payment'));

        $this->assertSame(TicketStatus::PaymentPending, $payment->ticket->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_an_unsigned_or_tampered_return_is_refused(): void
    {
        Mail::fake();
        $payment = $this->openPayment();
        $this->fakeKashierSession('PAID');

        $tampered = $this->signedRedirect();
        $tampered['amount'] = '1';

        $this->get(route('payments.kashier.return', ['merchantOrderId' => 'CCS-000001-ABC123', 'paymentStatus' => 'SUCCESS']))->assertForbidden();
        $this->get(route('payments.kashier.return', $tampered))->assertForbidden();

        $this->assertSame(TicketStatus::PaymentPending, $payment->ticket->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_a_failed_attempt_offers_the_same_link_again(): void
    {
        Mail::fake();
        $payment = $this->openPayment();
        $this->fakeKashierSession('PENDING');

        $this->get(route('payments.kashier.return', $this->signedRedirect('FAILURE')))
            ->assertOk()
            ->assertSee(__('Your payment did not go through'))
            ->assertSee($payment->session_url, false);

        $this->assertSame(TicketPaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_a_paid_amount_that_does_not_match_issues_nothing(): void
    {
        Mail::fake();
        Log::spy();
        $payment = $this->openPayment();
        $this->fakeKashierSession('PAID', ['amount' => '1.00']);

        $this->get(route('payments.kashier.return', $this->signedRedirect()))->assertOk();

        $this->assertSame(TicketStatus::PaymentPending, $payment->ticket->fresh()->status);
        $this->assertSame(TicketPaymentStatus::Pending, $payment->fresh()->status);
        Log::shouldHaveReceived('error')->once();
        Mail::assertNothingSent();
    }

    public function test_a_signed_webhook_issues_the_ticket(): void
    {
        Mail::fake();
        $payment = $this->openPayment();
        $this->fakeKashierSession('PAID');
        [$body, $signature] = $this->signedWebhook();

        $this->postJson(route('payments.kashier.webhook'), $body, ['x-kashier-signature' => $signature])->assertOk();

        $this->assertSame(TicketStatus::TicketIssued, $payment->ticket->fresh()->status);
        Mail::assertSent(TicketIssued::class);
    }

    public function test_a_webhook_with_a_bad_signature_is_refused(): void
    {
        Mail::fake();
        $payment = $this->openPayment();
        $this->fakeKashierSession('PAID');
        [$body] = $this->signedWebhook();

        $this->postJson(route('payments.kashier.webhook'), $body, ['x-kashier-signature' => str_repeat('0', 64)])->assertUnauthorized();
        $this->postJson(route('payments.kashier.webhook'), $body)->assertUnauthorized();

        $this->assertSame(TicketStatus::PaymentPending, $payment->ticket->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_the_webhook_needs_no_csrf_token(): void
    {
        // CSRF checks are switched off under test, so ask the middleware directly.
        $csrf = new class(app(), app('encrypter')) extends ValidateCsrfToken
        {
            public function skips(HttpRequest $request): bool
            {
                return $this->inExceptArray($request);
            }
        };

        $this->assertTrue($csrf->skips(HttpRequest::create('/payments/kashier/webhook', 'POST')));
        $this->assertFalse($csrf->skips(HttpRequest::create('/admin/payment-settings', 'POST')));
    }

    public function test_the_webhook_asks_kashier_to_retry_when_kashier_cannot_be_reached(): void
    {
        Mail::fake();
        $payment = $this->openPayment();
        Http::fake(['test-api.kashier.io/*' => Http::response('down', 500)]);
        [$body, $signature] = $this->signedWebhook();

        $this->postJson(route('payments.kashier.webhook'), $body, ['x-kashier-signature' => $signature])->assertStatus(503);

        $this->assertSame(TicketPaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_the_webhook_and_the_return_together_issue_one_ticket(): void
    {
        Mail::fake();
        $payment = $this->openPayment();
        $this->fakeKashierSession('PAID');
        [$body, $signature] = $this->signedWebhook();

        $this->postJson(route('payments.kashier.webhook'), $body, ['x-kashier-signature' => $signature])->assertOk();
        $issuedId = $payment->ticket->fresh()->ticket_id;
        $this->postJson(route('payments.kashier.webhook'), $body, ['x-kashier-signature' => $signature])->assertOk();
        $this->get(route('payments.kashier.return', $this->signedRedirect()))->assertOk();

        $this->assertSame($issuedId, $payment->ticket->fresh()->ticket_id);
        Mail::assertSentCount(1);
    }

    public function test_a_mail_failure_still_issues_the_ticket(): void
    {
        $payment = $this->openPayment();
        $this->fakeKashierSession('PAID');
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('Mail server down'));
        Log::spy();

        $response = $this->get(route('payments.kashier.return', $this->signedRedirect()));

        $ticket = $payment->ticket->fresh();
        $response->assertOk();
        $this->assertSame(TicketStatus::TicketIssued, $ticket->status);
        $response->assertSee(route('tickets.show', [$ticket, $ticket->ticket_id]), false);
        Log::shouldHaveReceived('error')->once();
    }

    public function test_a_payment_on_a_ticket_that_no_longer_needs_one_is_flagged_for_refund(): void
    {
        Mail::fake();
        Log::spy();
        $payment = $this->openPayment();
        $payment->ticket->update(['status' => TicketStatus::Cancelled]);
        $this->fakeKashierSession('PAID');

        $this->get(route('payments.kashier.return', $this->signedRedirect()))
            ->assertOk()
            ->assertSee(__('Payment received'));

        $this->assertSame(TicketStatus::Cancelled, $payment->ticket->fresh()->status);
        $this->assertSame(TicketPaymentStatus::Paid, $payment->fresh()->status);
        Log::shouldHaveReceived('critical')->once();
        Mail::assertNothingSent();
    }

    public function test_an_expired_session_is_recorded_as_expired(): void
    {
        Mail::fake();
        $payment = $this->openPayment();
        $this->fakeKashierSession('EXPIRED');

        $this->get(route('payments.kashier.return', $this->signedRedirect('FAILURE')))
            ->assertOk()
            ->assertSee(__('This payment link is no longer valid'));

        $this->assertSame(TicketPaymentStatus::Expired, $payment->fresh()->status);
        $this->assertSame(TicketStatus::PaymentPending, $payment->ticket->fresh()->status);
    }

    public function test_the_scheduled_sync_issues_a_ticket_whose_webhook_never_came(): void
    {
        Mail::fake();
        $payment = $this->openPayment();
        $settled = TicketPayment::factory()->paid()->create();
        $this->fakeKashierSession('PAID');

        $this->artisan('payments:sync')->assertSuccessful();

        $this->assertSame(TicketStatus::TicketIssued, $payment->ticket->fresh()->status);
        // Only the open link was asked about.
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'session123') && $request->header('Authorization') === ['test-secret-key']);
        $this->assertSame(TicketPaymentStatus::Paid, $settled->fresh()->status);
    }

    public function test_a_stale_second_issue_cannot_write_a_second_qr_code(): void
    {
        Mail::fake();
        $ticket = $this->openPayment()->ticket;
        $issuer = app(TicketIssuer::class);

        // Two requests that both loaded the ticket while it was still awaiting payment.
        $firstCopy = $ticket->fresh();
        $secondCopy = $ticket->fresh();

        $this->assertTrue($issuer->issue($firstCopy, 'kashier', TicketStatus::PaymentPending));
        $issuedId = $ticket->fresh()->ticket_id;
        $this->assertFalse($issuer->issue($secondCopy, 'kashier', TicketStatus::PaymentPending));

        $this->assertSame($issuedId, $ticket->fresh()->ticket_id);
    }
}
