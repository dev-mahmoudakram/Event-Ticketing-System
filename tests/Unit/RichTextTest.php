<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\RichText;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * RichText::clean() is the actual security boundary for every CMS body field in the app —
 * CKEditor only constrains the editor UI, not a hand-crafted request. These prove common
 * stored-XSS payloads are neutralized, and that ordinary formatting survives untouched.
 */
class RichTextTest extends TestCase
{
    #[DataProvider('xssPayloads')]
    public function test_dangerous_markup_is_stripped(string $payload, string $mustNotContain): void
    {
        $cleaned = RichText::clean($payload);

        $this->assertStringNotContainsString($mustNotContain, (string) $cleaned);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function xssPayloads(): array
    {
        return [
            'script tag' => ['<p>Hello</p><script>alert(1)</script>', '<script'],
            'img onerror' => ['<img src=x onerror=alert(1)>', 'onerror'],
            'javascript: href' => ['<a href="javascript:alert(1)">click</a>', 'javascript:'],
            'onclick attribute' => ['<p onclick="alert(1)">text</p>', 'onclick'],
            'style-based javascript url' => ['<p style="background:url(javascript:alert(1))">styled</p>', 'javascript:'],
            'style attribute at all' => ['<p style="color:red">styled</p>', 'style='],
            'iframe' => ['<iframe src="//evil.com"></iframe>', '<iframe'],
            'onmouseover on a real link' => ['<a href="https://example.com" onmouseover="alert(1)">link</a>', 'onmouseover'],
            'svg onload' => ['<svg onload=alert(1)></svg>', 'onload'],
            'data: uri script' => ['<a href="data:text/html,<script>alert(1)</script>">x</a>', 'data:'],
            'class attribute (not on the allowlist)' => ['<p class="evil">text</p>', 'class='],
            'id attribute (not on the allowlist)' => ['<p id="evil">text</p>', 'id='],
            'form element' => ['<form action="//evil.com"><input></form>', '<form'],
            'object element' => ['<object data="//evil.com"></object>', '<object'],
            'embed element' => ['<embed src="//evil.com">', '<embed'],
        ];
    }

    public function test_ordinary_formatting_survives_unchanged_in_shape(): void
    {
        $cleaned = RichText::clean(
            '<p>Normal <strong>bold</strong> and <em>italic</em> text with a <a href="https://example.com">link</a>.</p>'
        );

        $this->assertStringContainsString('<strong>bold</strong>', (string) $cleaned);
        $this->assertStringContainsString('<em>italic</em>', (string) $cleaned);
        $this->assertStringContainsString('<a href="https://example.com">link</a>', (string) $cleaned);
    }

    public function test_headings_lists_and_blockquotes_are_allowed(): void
    {
        $cleaned = RichText::clean('<h2>Title</h2><ul><li>One</li><li>Two</li></ul><blockquote>Quote</blockquote>');

        $this->assertStringContainsString('<h2>Title</h2>', (string) $cleaned);
        $this->assertStringContainsString('<li>One</li>', (string) $cleaned);
        $this->assertStringContainsString('<blockquote>', (string) $cleaned);
    }

    public function test_a_mailto_link_is_allowed(): void
    {
        $cleaned = RichText::clean('<a href="mailto:team@example.com">Email us</a>');

        $this->assertStringContainsString('href="mailto:team@example.com"', (string) $cleaned);
    }

    public function test_null_passes_through_unchanged(): void
    {
        $this->assertNull(RichText::clean(null));
    }

    public function test_empty_string_passes_through_unchanged(): void
    {
        $this->assertSame('', RichText::clean(''));
    }

    public function test_target_blank_is_not_injected_onto_links(): void
    {
        // TargetBlank would give an attacker-controlled href a window.opener reference into
        // this page; the CMS never needs it, so it stays off.
        $cleaned = RichText::clean('<a href="https://example.com">link</a>');

        $this->assertStringNotContainsString('target=', (string) $cleaned);
    }
}
