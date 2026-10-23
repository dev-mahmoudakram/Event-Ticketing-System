<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\TurnstileVerifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TurnstileVerifierTest extends TestCase
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.turnstile.site_key' => 'test-site-key', 'services.turnstile.secret_key' => 'test-secret']);
    }

    public function test_a_successful_check_passes(): void
    {
        Http::fake([self::VERIFY_URL => Http::response(['success' => true])]);

        $this->assertTrue(app(TurnstileVerifier::class)->verify('token-abc', '10.0.0.1'));

        Http::assertSent(fn (Request $request): bool => $request->url() === self::VERIFY_URL
            && $request->method() === 'POST'
            && $request['secret'] === 'test-secret'
            && $request['response'] === 'token-abc'
            && $request['remoteip'] === '10.0.0.1');
    }

    public function test_a_failed_check_fails(): void
    {
        Http::fake([self::VERIFY_URL => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']])]);

        $this->assertFalse(app(TurnstileVerifier::class)->verify('token-abc', '10.0.0.1'));
    }

    public function test_a_server_error_fails(): void
    {
        Http::fake([self::VERIFY_URL => Http::response(['success' => true], 500)]);

        $this->assertFalse(app(TurnstileVerifier::class)->verify('token-abc', '10.0.0.1'));
    }

    public function test_a_missing_token_fails_without_asking_cloudflare(): void
    {
        Http::fake();

        $this->assertFalse(app(TurnstileVerifier::class)->verify(null, '10.0.0.1'));
        $this->assertFalse(app(TurnstileVerifier::class)->verify('', '10.0.0.1'));

        Http::assertNothingSent();
    }

    public function test_a_missing_secret_logs_a_warning_and_fails_closed(): void
    {
        Http::fake();
        Log::spy();
        config(['services.turnstile.secret_key' => '']);

        $this->assertFalse(app(TurnstileVerifier::class)->verify('token-abc', '10.0.0.1'));

        Http::assertNothingSent();
        Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message): bool => str_contains($message, 'Turnstile'));
    }

    public function test_a_missing_site_key_logs_a_warning_and_fails_closed(): void
    {
        Http::fake();
        Log::spy();
        config(['services.turnstile.site_key' => null]);

        $this->assertFalse(app(TurnstileVerifier::class)->verify('token-abc', '10.0.0.1'));

        Http::assertNothingSent();
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_an_unreachable_cloudflare_fails(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $this->assertFalse(app(TurnstileVerifier::class)->verify('token-abc', '10.0.0.1'));
    }
}
