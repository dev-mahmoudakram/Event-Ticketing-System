<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Reel;
use App\Models\Sponsor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReelSectionTest extends TestCase
{
    use RefreshDatabase;

    private function publishedEvent(): Event
    {
        return Event::factory()->create(['status' => EventStatus::Published]);
    }

    public function test_reel_section_renders_when_the_event_has_reels(): void
    {
        $event = $this->publishedEvent();
        Reel::factory()->for($event)->create(['caption_en' => 'Awards Night']);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('id="reel"', false);
        $response->assertSee('data-reel-stage', false);
        $response->assertSee('Awards Night');
    }

    public function test_reel_section_is_hidden_when_there_are_no_reels(): void
    {
        $event = $this->publishedEvent();

        $response = $this->get(route('landing.show', $event));

        $response->assertDontSee('data-reel-stage', false);
    }

    public function test_reel_section_respects_the_visibility_toggle(): void
    {
        $event = $this->publishedEvent();
        $event->update(['visible_sections' => ['reel' => false]]);
        Reel::factory()->for($event)->create();

        $response = $this->get(route('landing.show', $event));

        $response->assertDontSee('data-reel-stage', false);
    }

    public function test_hero_shows_reel_clips_as_floating_cards(): void
    {
        $event = $this->publishedEvent();
        Reel::factory()->for($event)->create(['video_path' => 'reels/hero-clip.mp4']);

        $response = $this->get(route('landing.show', $event));

        $response->assertSee('data-hero-phone', false);
        $response->assertSee('reels/hero-clip.mp4', false);
    }

    /**
     * The clip ships with no source at all until JS asks it to play — a video that already
     * carries `src` with preload="metadata" downloads on every visit, including a hero clip
     * that a phone below the `lg` breakpoint can never even see.
     */
    public function test_reel_and_hero_clips_carry_no_source_until_javascript_asks_for_one(): void
    {
        $event = $this->publishedEvent();
        Reel::factory()->for($event)->create(['video_path' => 'reels/hero-clip.mp4']);

        $html = $this->get(route('landing.show', $event))->getContent();

        // "src=" alone would also match inside "data-src=", so the check looks for the
        // attribute preceded by a space the way the browser parser sees it.
        $this->assertStringNotContainsString(' src="/storage/reels/hero-clip.mp4"', $html);
        $this->assertStringContainsString('data-src="/storage/reels/hero-clip.mp4"', $html);
        $this->assertStringContainsString('preload="none"', $html);
    }

    public function test_landing_page_renders_the_scroll_progress_bar(): void
    {
        $event = $this->publishedEvent();

        $response = $this->get(route('landing.show', $event));

        $response->assertSee('data-scroll-progress', false);
    }

    public function test_sponsor_logo_is_rendered_as_an_image(): void
    {
        $event = $this->publishedEvent();
        Sponsor::factory()->for($event)->create([
            'logo_path' => 'sponsors/acme.png',
            'name_en' => 'Acme',
            'website_url' => null,
        ]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('/storage/sponsors/acme.png', false);
        $response->assertSee('alt="Acme"', false);
    }

    public function test_sponsor_without_a_logo_falls_back_to_its_name(): void
    {
        $event = $this->publishedEvent();
        Sponsor::factory()->for($event)->create(['logo_path' => null, 'name_en' => 'Nameless Co']);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('Nameless Co');
    }

    public function test_an_absolute_media_url_is_left_untouched(): void
    {
        $event = $this->publishedEvent();
        Sponsor::factory()->for($event)->create(['logo_path' => 'https://cdn.example.com/logo.png']);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('https://cdn.example.com/logo.png', false);
        $response->assertDontSee('/storage/https://', false);
    }
}
