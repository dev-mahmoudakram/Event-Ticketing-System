<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Event;
use App\Models\SiteContent;
use App\Models\User;
use App\Support\SiteText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandContactDetailsTest extends TestCase
{
    use RefreshDatabase;

    private function hubDetail(string $field, string $value): void
    {
        SiteContent::updateOrCreate(
            ['section' => 'contact_details', 'field_key' => $field],
            ['value_ar' => $value, 'value_en' => $value],
        );
        SiteText::flush();
    }

    public function test_the_hub_footer_lists_the_platform_details(): void
    {
        $this->hubDetail('email', 'hello@creatorshub.test');
        $this->hubDetail('phone', '+20 100 000 0000');
        $this->hubDetail('instagram', 'https://instagram.com/creatorshub');

        $response = $this->get(route('home').'?lang=en');

        $response->assertSee('mailto:hello@creatorshub.test', false);
        $response->assertSee('tel:+201000000000', false);
        $response->assertSee('href="https://instagram.com/creatorshub"', false);
        $response->assertSee('aria-label="Instagram"', false);
    }

    public function test_the_hub_footer_stays_quiet_until_the_details_are_filled_in(): void
    {
        $response = $this->get(route('home').'?lang=en');

        $response->assertDontSee('mailto:', false);
        $response->assertDontSee('aria-label="Instagram"', false);
    }

    public function test_an_event_lists_its_own_details_rather_than_the_platform_ones(): void
    {
        $this->hubDetail('email', 'hello@creatorshub.test');
        $this->hubDetail('instagram', 'https://instagram.com/creatorshub');

        $event = Event::factory()->create([
            'status' => 'published',
            'contact_email' => 'tickets@ccs.test',
            'social_links' => ['instagram' => 'https://instagram.com/ccs'],
        ]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('mailto:tickets@ccs.test', false);
        $response->assertSee('href="https://instagram.com/ccs"', false);
        $response->assertDontSee('hello@creatorshub.test', false);
        $response->assertDontSee('instagram.com/creatorshub', false);
    }

    public function test_only_known_networks_are_kept(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($admin)->put(route('admin.events.update', $event), [
            'slug' => $event->slug,
            'name_ar' => $event->name_ar,
            'name_en' => $event->name_en,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-02',
            'status' => 'published',
            'social_links' => [
                'instagram' => 'https://instagram.com/ccs',
                'myspace' => 'https://myspace.com/ccs',
                'facebook' => '',
            ],
        ])->assertRedirect(route('admin.events.index'));

        $this->assertSame(['instagram' => 'https://instagram.com/ccs'], $event->fresh()->social_links);
    }

    public function test_an_event_can_carry_its_own_icons_and_link_preview(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($admin)->put(route('admin.events.update', $event), [
            'slug' => $event->slug,
            'name_ar' => $event->name_ar,
            'name_en' => $event->name_en,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-02',
            'status' => 'published',
            'favicon' => UploadedFile::fake()->image('icon.png'),
            'apple_touch_icon' => UploadedFile::fake()->image('touch.png'),
            'share_image' => UploadedFile::fake()->image('share.png'),
        ]);

        $event = $event->fresh();
        foreach (['favicon_path', 'apple_touch_icon_path', 'share_image_path'] as $column) {
            $this->assertNotNull($event->{$column}, $column.' was not stored.');
            Storage::disk('public')->assertExists($event->{$column});
        }
    }

    public function test_an_event_page_wears_its_own_icons(): void
    {
        $event = Event::factory()->create([
            'status' => 'published',
            'name_en' => 'Cairo Design Week',
            'favicon_path' => 'events/icon.png',
            'apple_touch_icon_path' => 'events/touch.png',
        ]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('<link rel="icon" href="/storage/events/icon.png">', false);
        $response->assertSee('<link rel="apple-touch-icon" sizes="180x180" href="/storage/events/touch.png">', false);
        $response->assertSee('<meta name="apple-mobile-web-app-title" content="Cairo Design Week">', false);
        // The manifest names the platform, so an event does not claim it.
        $response->assertDontSee('site.webmanifest', false);
    }

    public function test_an_event_without_icons_borrows_the_platform_set(): void
    {
        $event = Event::factory()->create(['status' => 'published']);

        $this->get(route('landing.show', $event).'?lang=en')
            ->assertSee('<link rel="icon" type="image/svg+xml" href="/favicon.svg">', false);
    }

    public function test_a_link_preview_image_beats_the_cover_image(): void
    {
        $event = Event::factory()->create([
            'status' => 'published',
            'cover_image_path' => 'events/cover.jpg',
            'share_image_path' => 'events/share.jpg',
        ]);

        $this->get(route('landing.show', $event).'?lang=en')
            ->assertSee('<meta property="og:image" content="'.url('/storage/events/share.jpg').'">', false);
    }

    public function test_the_hub_settings_screen_saves_a_link_once_for_both_languages(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.site-content.update', 'contact_details'), [
            'single' => ['email' => 'hello@creatorshub.test', 'instagram' => ''],
        ])->assertRedirect(route('admin.site-content.edit', 'contact_details'));

        $stored = SiteContent::where(['section' => 'contact_details', 'field_key' => 'email'])->firstOrFail();
        $this->assertSame('hello@creatorshub.test', $stored->value_en);
        $this->assertSame('hello@creatorshub.test', $stored->value_ar);
        $this->assertNull(SiteContent::where(['section' => 'contact_details', 'field_key' => 'instagram'])->firstOrFail()->value_en);
    }
}
