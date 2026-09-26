<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_send_the_hardening_headers(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');
    }

    public function test_the_content_security_policy_only_reports_for_now(): void
    {
        $response = $this->get(route('home'));

        $response->assertHeaderMissing('Content-Security-Policy');
        $policy = $response->headers->get('Content-Security-Policy-Report-Only');

        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("frame-ancestors 'self'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
    }

    public function test_admin_pages_send_the_headers_too(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.events.index'))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    /**
     * Inline onclick/onsubmit handlers stop working once the policy enforces without
     * 'unsafe-inline' — use data-confirm, data-select-on-click or data-print instead.
     */
    public function test_no_view_uses_inline_event_handlers(): void
    {
        $offenders = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (preg_match('/\son(?:click|submit|change|input|load|error|focus|blur|key\w+|mouse\w+)\s*=/i', $file->getContents()) === 1) {
                $offenders[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $offenders);
    }

    public function test_hsts_is_sent_only_over_https(): void
    {
        $this->get(route('home'))->assertHeaderMissing('Strict-Transport-Security');

        $this->get(str_replace('http://', 'https://', route('home')))
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }
}
