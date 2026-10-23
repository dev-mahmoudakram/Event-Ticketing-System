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
 * Missing site or secret keys also fail closed, with a warning in the log so it gets noticed.
 */
class TurnstileVerifier
{
    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public const TIMEOUT_SECONDS = 5;

    public function verify(?string $token, ?string $ip): bool
    {
        $siteKey = (string) config('services.turnstile.site_key');
        $secretKey = (string) config('services.turnstile.secret_key');

        if ($siteKey === '' || $secretKey === '') {
            Log::warning('Cloudflare Turnstile keys are not configured; refusing the security check.', [
                'missing' => array_keys(array_filter(['site_key' => $siteKey === '', 'secret_key' => $secretKey === ''])),
            ]);

            return false;
        }

        if ($token === null || $token === '') {
            return false;
        }

        $payload = [
            'secret' => $secretKey,
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
