<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Each test makes a single request: Symfony keeps the trusted-proxy settings in static state,
 * so a second request in the same test would inherit the first one's.
 */
class CloudflareProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_ip', fn (Request $request) => $request->ip());
    }

    public function test_a_request_through_cloudflare_uses_the_visitors_ip(): void
    {
        $this->trustCloudflare();

        $this->withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
            ->withHeader('X-Forwarded-For', '41.33.10.5')
            ->get('/_ip')
            ->assertSeeText('41.33.10.5');
    }

    public function test_a_direct_request_cannot_fake_its_ip(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->withHeader('X-Forwarded-For', '41.33.10.5')
            ->get('/_ip')
            ->assertSeeText('203.0.113.7');
    }

    public function test_ipv6_cloudflare_ranges_are_trusted(): void
    {
        $this->trustCloudflare();

        $this->withServerVariables(['REMOTE_ADDR' => '2400:cb00::1'])
            ->withHeader('X-Forwarded-For', '41.33.10.5')
            ->get('/_ip')
            ->assertSeeText('41.33.10.5');
    }

    public function test_cloudflare_is_not_trusted_by_default(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
            ->withHeader('X-Forwarded-For', '41.33.10.5')
            ->get('/_ip')
            ->assertSeeText('173.245.48.10');
    }

    public function test_https_scheme_is_taken_from_a_trusted_proxy(): void
    {
        $this->trustCloudflare();

        Route::get('/_secure', fn (Request $request) => $request->isSecure() ? 'secure' : 'plain');

        $this->withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
            ->withHeader('X-Forwarded-Proto', 'https')
            ->get('/_secure')
            ->assertSeeText('secure');
    }

    public function test_https_scheme_is_ignored_from_an_untrusted_caller(): void
    {
        Route::get('/_secure', fn (Request $request) => $request->isSecure() ? 'secure' : 'plain');

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->withHeader('X-Forwarded-Proto', 'https')
            ->get('/_secure')
            ->assertSeeText('plain');
    }

    public function test_a_local_reverse_proxy_is_trusted(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeader('X-Forwarded-For', '41.33.10.5')
            ->get('/_ip')
            ->assertSeeText('41.33.10.5');
    }

    public function test_the_workers_egress_range_is_not_trusted(): void
    {
        $this->trustCloudflare();

        $this->withServerVariables(['REMOTE_ADDR' => '2a06:98c0::1'])
            ->withHeader('X-Forwarded-For', '41.33.10.5')
            ->get('/_ip')
            ->assertSeeText('2a06:98c0::1');
    }

    public function test_extra_proxies_come_from_the_trusted_proxies_env_value(): void
    {
        $this->useTrustedProxiesEnv('198.51.100.0/24, 192.0.2.9');

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->withHeader('X-Forwarded-For', '41.33.10.5')
            ->get('/_ip')
            ->assertSeeText('41.33.10.5');
    }

    public function test_callers_outside_the_trusted_proxies_env_value_are_still_untrusted(): void
    {
        $this->useTrustedProxiesEnv('198.51.100.0/24, 192.0.2.9');

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->withHeader('X-Forwarded-For', '41.33.10.5')
            ->get('/_ip')
            ->assertSeeText('203.0.113.7');
    }

    public function test_a_star_in_the_trusted_proxies_env_value_trusts_every_caller(): void
    {
        $this->useTrustedProxiesEnv('*');

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->withHeader('X-Forwarded-For', '41.33.10.5')
            ->get('/_ip')
            ->assertSeeText('41.33.10.5');
    }

    public function test_localhost_is_trusted_and_cloudflare_is_not_by_default(): void
    {
        $this->useEnv(['TRUSTED_PROXIES' => '']);

        $proxies = config('trustedproxy.proxies');

        $this->assertContains('127.0.0.1', $proxies);
        $this->assertContains('::1', $proxies);
        $this->assertNotContains('173.245.48.0/20', $proxies);
    }

    public function test_cloudflare_ranges_are_added_when_trust_cloudflare_is_set(): void
    {
        $this->trustCloudflare();

        $proxies = config('trustedproxy.proxies');

        $this->assertContains('173.245.48.0/20', $proxies);
        $this->assertContains('2400:cb00::/32', $proxies);
        $this->assertContains('127.0.0.1', $proxies);
    }

    private function trustCloudflare(): void
    {
        $this->useEnv(['TRUST_CLOUDFLARE' => 'true']);
    }

    private function useTrustedProxiesEnv(string $value): void
    {
        $this->useEnv(['TRUSTED_PROXIES' => $value]);
    }

    /**
     * Sets env values and rebuilds the trusted-proxy config from them.
     *
     * @param  array<string, string>  $values
     */
    private function useEnv(array $values): void
    {
        foreach ($values as $name => $value) {
            $_ENV[$name] = $_SERVER[$name] = $value;
            putenv($name.'='.$value);
        }

        $this->beforeApplicationDestroyed(function () use ($values): void {
            foreach (array_keys($values) as $name) {
                unset($_ENV[$name], $_SERVER[$name]);
                putenv($name);
            }
        });

        config(['trustedproxy' => require config_path('trustedproxy.php')]);
    }
}
