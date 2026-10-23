<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Asks Cloudflare whether a Turnstile token from the browser is genuine.
 *
 * Anything other than a clear "yes" — no token, an error reply, a timeout — counts as a fail.
 */
class TurnstileVerifier
{
    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public const TIMEOUT_SECONDS = 5;

    public function verify(?string $token, ?string $ip): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $payload = [
            'secret' => (string) config('services.turnstile.secret_key'),
            'response' => $token,
        ];

        if ($ip !== null && $ip !== '') {
            $payload['remoteip'] = $ip;
        }

        try {
            $response = Http::asForm()->timeout(self::TIMEOUT_SECONDS)->post(self::VERIFY_URL, $payload);
        } catch (ConnectionException $exception) {
            Log::warning('Could not reach Cloudflare Turnstile.', ['message' => $exception->getMessage()]);

            return false;
        }

        return $response->successful() && $response->json('success') === true;
    }
}
