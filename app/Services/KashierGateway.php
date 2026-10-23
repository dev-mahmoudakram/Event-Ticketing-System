<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PaymentSetting;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Talks to Kashier's hosted payment sessions API, and checks what Kashier sends back.
 *
 * Every URL handed to Kashier is built here from APP_URL, never from the incoming request, so a
 * forged Host header can't send a customer anywhere else after paying. Every URL taken from
 * Kashier is checked against its checkout host before anyone is sent to it.
 */
class KashierGateway
{
    private const CHECKOUT_URL_PREFIX = 'https://payments.kashier.io/';

    /**
     * The redirect fields Kashier signs, in the order it signs them.
     */
    private const REDIRECT_SIGNED_FIELDS = [
        'paymentStatus', 'cardDataToken', 'maskedCard', 'merchantOrderId', 'orderId',
        'cardBrand', 'orderReference', 'transactionId', 'amount', 'currency',
    ];

    /**
     * Open a one-time checkout session for an amount, and return Kashier's id and URL for it.
     *
     * @param  array{order: string, amount: int, currency: string, expires_at: CarbonInterface, email: string, reference: string, description: string, locale: string}  $payment
     * @return array{session_id: string, session_url: string}
     *
     * @throws PaymentGatewayException
     */
    public function createSession(array $payment): array
    {
        $settings = $this->settings();

        $response = $this->send(fn () => $this->client($settings)
            ->withHeaders(['api-key' => (string) $settings->api_key])
            ->post('/v3/payment/sessions', [
                'expireAt' => $payment['expires_at']->toIso8601ZuluString('millisecond'),
                'maxFailureAttempts' => 5,
                'paymentType' => 'credit',
                'amount' => $this->formatAmount($payment['amount']),
                'currency' => $payment['currency'],
                'order' => $payment['order'],
                'merchantId' => $settings->merchant_id,
                'merchantRedirect' => $this->appUrl('payments.kashier.return'),
                'serverWebhook' => $this->appUrl('payments.kashier.webhook'),
                'failureRedirect' => false,
                'display' => $payment['locale'] === 'ar' ? 'ar' : 'en',
                'type' => 'one-time',
                'description' => mb_substr($payment['description'], 0, 119),
                'customer' => ['email' => $payment['email'], 'reference' => $payment['reference']],
            ]));

        $sessionId = $response['_id'] ?? null;
        $sessionUrl = $response['sessionUrl'] ?? null;

        if (! is_string($sessionId) || $sessionId === '' || ! is_string($sessionUrl) || ! str_starts_with($sessionUrl, self::CHECKOUT_URL_PREFIX)) {
            throw new PaymentGatewayException('Kashier returned a session without a usable checkout URL.');
        }

        return ['session_id' => $sessionId, 'session_url' => $sessionUrl];
    }

    /**
     * Kashier's own record of a session: its status, order reference, amount and currency.
     *
     * @return array{status: string, merchantOrderId: string|null, amount: string|null, currency: string|null}
     *
     * @throws PaymentGatewayException
     */
    public function fetchSession(string $sessionId): array
    {
        $response = $this->send(fn () => $this->client($this->settings())
            ->get('/v3/payment/sessions/'.rawurlencode($sessionId).'/payment'));

        $data = $response['data'] ?? null;

        if (! is_array($data) || ! is_string($data['status'] ?? null)) {
            throw new PaymentGatewayException('Kashier returned a session without a status.');
        }

        return [
            'status' => $data['status'],
            'merchantOrderId' => isset($data['merchantOrderId']) ? (string) $data['merchantOrderId'] : null,
            'amount' => isset($data['amount']) ? (string) $data['amount'] : null,
            'currency' => isset($data['currency']) ? (string) $data['currency'] : null,
        ];
    }

    /**
     * Close a session nobody should pay any more. It cannot undo a payment already made.
     *
     * @throws PaymentGatewayException
     */
    public function abandonSession(string $sessionId): void
    {
        $this->send(fn () => $this->client($this->settings())
            ->patch('/v3/payment/sessions/'.rawurlencode($sessionId), []));
    }

    /**
     * Whether a webhook body really came from Kashier: an HMAC-SHA256 of the fields it lists in
     * signatureKeys, sorted and query-encoded, keyed with the Payment API key.
     *
     * @param  array<string, mixed>  $data
     */
    public function hasValidWebhookSignature(array $data, ?string $signature): bool
    {
        $keys = $data['signatureKeys'] ?? null;

        if (blank($signature) || ! is_array($keys) || $keys === []) {
            return false;
        }

        $keys = array_values(array_filter($keys, 'is_string'));
        sort($keys);

        $signed = [];
        foreach ($keys as $key) {
            $signed[$key] = $data[$key] ?? null;
        }

        return $this->matches(http_build_query($signed, '', '&', PHP_QUERY_RFC3986), (string) $signature);
    }

    /**
     * Whether the query string Kashier sent the customer back with is genuine. A field Kashier
     * left out is signed as the word "null".
     *
     * @param  array<string, mixed>  $query
     */
    public function hasValidRedirectSignature(array $query): bool
    {
        $signature = $query['signature'] ?? null;

        if (! is_string($signature) || $signature === '') {
            return false;
        }

        $parts = [];
        foreach (self::REDIRECT_SIGNED_FIELDS as $field) {
            $value = $query[$field] ?? null;
            $parts[] = $field.'='.(is_scalar($value) ? (string) $value : 'null');
        }

        return $this->matches(implode('&', $parts), $signature);
    }

    /**
     * Kashier writes amounts as decimal strings, e.g. 1500 → "1500.00".
     */
    public function formatAmount(int $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    private function matches(string $payload, string $signature): bool
    {
        $apiKey = (string) $this->settings()->api_key;

        if ($apiKey === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $payload, $apiKey), strtolower($signature));
    }

    /**
     * @throws PaymentGatewayException
     */
    private function settings(): PaymentSetting
    {
        $settings = PaymentSetting::current();

        if (! $settings->isComplete()) {
            throw new PaymentGatewayException('The Kashier payment settings are incomplete.');
        }

        return $settings;
    }

    private function client(PaymentSetting $settings): PendingRequest
    {
        return Http::baseUrl($settings->mode->apiBaseUrl())
            ->withHeaders(['Authorization' => (string) $settings->secret_key])
            ->acceptJson()
            ->asJson()
            ->connectTimeout(10)
            ->timeout(20);
    }

    /**
     * @param  callable(): Response  $request
     * @return array<string, mixed>
     *
     * @throws PaymentGatewayException
     */
    private function send(callable $request): array
    {
        try {
            $response = $request();
        } catch (ConnectionException $exception) {
            throw new PaymentGatewayException('Kashier could not be reached.', previous: $exception);
        }

        if ($response->failed()) {
            throw new PaymentGatewayException('Kashier refused the request (HTTP '.$response->status().'): '.mb_substr($response->body(), 0, 500));
        }

        $body = $response->json();

        return is_array($body) ? $body : [];
    }

    /**
     * An absolute URL to one of our routes, rooted at APP_URL rather than the request's host.
     */
    private function appUrl(string $routeName): string
    {
        return rtrim((string) config('app.url'), '/').route($routeName, [], false);
    }
}
