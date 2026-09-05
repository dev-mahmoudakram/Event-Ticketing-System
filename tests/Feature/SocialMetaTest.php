<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Event;
use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialMetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_page_describes_itself_to_crawlers(): void
    {
        $response = $this->get(route('home').'?lang=en');

        $response->assertSee('<meta property="og:site_name" content="Creators Hub">', false);
        $response->assertSee('<meta property="og:type" content="website">', false);
        $response->assertSee('<meta property="og:title" content="Creators Hub', false);
        $response->assertSee('<meta property="og:url" content="'.url('/').'">', false);
        $response->assertSee('<link rel="canonical" href="'.url('/').'">', false);
        $response->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
    }

    public function test_both_languages_are_offered_to_crawlers(): void
    {
        $response = $this->get(route('home').'?lang=en');

        $response->assertSee('hreflang="ar"', false);
        $response->assertSee('hreflang="en"', false);
        $response->assertSee('hreflang="x-default"', false);
        $response->assertSee('<meta property="og:locale" content="en_US">', false);
        $response->assertSee('<meta property="og:locale:alternate" content="ar_EG">', false);
    }

    public function test_the_page_is_described_in_the_reading_language(): void
    {
        $this->get(route('home').'?lang=ar')
            ->assertSee('<meta property="og:locale" content="ar_EG">', false);
    }

    public function test_a_preview_image_carries_its_size_when_this_app_serves_it(): void
    {
        $response = $this->get(route('home').'?lang=en');

        $response->assertSee('<meta property="og:image" content="'.url('/images/creators-hub/Logo.png').'">', false);
        $response->assertSee('<meta property="og:image:type" content="image/png">', false);
        $response->assertSee('og:image:width', false);
        $response->assertSee('og:image:height', false);
    }

    public function test_an_uploaded_preview_image_replaces_the_logo(): void
    {
        SiteContent::create([
            'section' => 'sharing',
            'field_key' => 'image',
            'value_en' => '/storage/site/share.png',
            'value_ar' => '/storage/site/share.png',
        ]);

        $this->get(route('home').'?lang=en')
            ->assertSee('<meta property="og:image" content="'.url('/storage/site/share.png').'">', false);
    }

    public function test_an_event_page_shares_the_event_rather_than_the_platform(): void
    {
        $event = Event::factory()->create([
            'status' => 'published',
            'name_en' => 'Cairo Design Week',
            'tagline_en' => 'Three days of rooms worth arguing about',
            'cover_image_path' => 'events/cover.jpg',
        ]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('<meta property="og:title" content="Cairo Design Week">', false);
        $response->assertSee('<meta property="og:description" content="Three days of rooms worth arguing about">', false);
        $response->assertSee('<meta property="og:image" content="'.url('/storage/events/cover.jpg').'">', false);
    }

    public function test_an_event_without_a_cover_falls_back_to_a_small_card(): void
    {
        $event = Event::factory()->create(['status' => 'published', 'cover_image_path' => null]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('<meta name="twitter:card" content="summary">', false);
        $response->assertDontSee('og:image', false);
    }

    public function test_every_page_carries_the_icon_set(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('<link rel="icon" type="image/svg+xml" href="/favicon.svg">', false);
        $response->assertSee('<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">', false);
        $response->assertSee('<link rel="manifest" href="/site.webmanifest">', false);
        $response->assertSee('<meta name="apple-mobile-web-app-title" content="CreatorsHub">', false);
    }

    public function test_the_icon_files_are_published(): void
    {
        foreach ([
            'favicon.ico', 'favicon.svg', 'favicon-96x96.png', 'apple-touch-icon.png',
            'site.webmanifest', 'web-app-manifest-192x192.png', 'web-app-manifest-512x512.png',
        ] as $file) {
            $this->assertFileExists(public_path($file), $file.' is missing from public/.');
        }
    }
}
