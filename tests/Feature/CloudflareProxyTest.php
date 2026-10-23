<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CloudflareProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_ip', fn (Request $request) => $request->ip());
    }

    public function test_a_request_through_cloudflare_uses_the_visitors_ip(): void
    {
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
        $this->withServerVariables(['REMOTE_ADDR' => '2400:cb00::1'])
            ->withHeader('X-Forwarded-For', '41.33.10.5')
            ->get('/_ip')
            ->assertSeeText('41.33.10.5');
    }
}
