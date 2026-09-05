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

class BrandLogoTest extends TestCase
{
    use RefreshDatabase;

    private function hubLogo(string $field, string $path): void
    {
        SiteContent::updateOrCreate(
            ['section' => 'branding', 'field_key' => $field],
            ['value_ar' => $path, 'value_en' => $path],
        );
        SiteText::flush();
    }

    public function test_the_hub_uses_an_uploaded_logo_in_both_places(): void
    {
        $this->hubLogo('nav_logo', 'site/nav-logo.png');
        $this->hubLogo('footer_logo', 'site/footer-logo.png');

        $response = $this->get(route('home').'?lang=en');

        $response->assertSee('/storage/site/nav-logo.png', false);
        $response->assertSee('/storage/site/footer-logo.png', false);
        $response->assertDontSee('creators-hub/mark.png', false);
    }

    public function test_the_footer_falls_back_to_the_navigation_logo(): void
    {
        $this->hubLogo('nav_logo', 'site/nav-logo.png');

        $html = $this->get(route('home').'?lang=en')->getContent();

        $this->assertSame(2, substr_count($html, '/storage/site/nav-logo.png'), 'The nav logo should also dress the footer.');

        // The white mark still appears as decoration elsewhere; the footer is what matters here.
        $footer = substr($html, strpos($html, '<footer'));
        $this->assertStringNotContainsString('mark-white.png', $footer);
    }

    public function test_the_shipped_mark_stays_until_a_logo_is_uploaded(): void
    {
        $response = $this->get(route('home').'?lang=en');

        $response->assertSee('creators-hub/mark.png', false);
        $response->assertSee('creators-hub/mark-white.png', false);
    }

    public function test_an_event_shows_its_own_logo(): void
    {
        $event = Event::factory()->create([
            'status' => 'published',
            'name_en' => 'Cairo Design Week',
            'logo_path' => 'events/logo.png',
            'footer_logo_path' => 'events/logo-light.png',
        ]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('/storage/events/logo.png', false);
        $response->assertSee('/storage/events/logo-light.png', false);
        $response->assertSee('alt="Cairo Design Week"', false);
    }

    public function test_an_event_footer_falls_back_to_the_navigation_logo(): void
    {
        $event = Event::factory()->create(['status' => 'published', 'logo_path' => 'events/logo.png']);

        $html = $this->get(route('landing.show', $event).'?lang=en')->getContent();

        $this->assertSame(2, substr_count($html, '/storage/events/logo.png'));
    }

    public function test_an_event_without_a_logo_keeps_its_wordmark(): void
    {
        $event = Event::factory()->create(['status' => 'published', 'start_date' => '2026-08-15', 'end_date' => '2026-08-16']);

        $this->get(route('landing.show', $event).'?lang=en')->assertSee('CCS <span class="text-ccs-coral">2026</span>', false);
    }

    public function test_an_admin_can_upload_both_event_logos(): void
    {
        Storage::fake('public');
        $event = Event::factory()->create();

        $this->actingAs(User::factory()->create())->put(route('admin.events.update', $event), [
            'slug' => $event->slug,
            'name_ar' => $event->name_ar,
            'name_en' => $event->name_en,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-02',
            'status' => 'published',
            'logo' => UploadedFile::fake()->image('logo.png'),
            'footer_logo' => UploadedFile::fake()->image('logo-light.png'),
        ])->assertRedirect(route('admin.events.index'));

        $event = $event->fresh();
        Storage::disk('public')->assertExists($event->logo_path);
        Storage::disk('public')->assertExists($event->footer_logo_path);
    }

    public function test_the_hub_logo_screen_accepts_an_upload(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->put(route('admin.site-content.update', 'branding'), [
                'images' => ['nav_logo' => UploadedFile::fake()->image('logo.svg')],
            ])
            ->assertRedirect(route('admin.site-content.edit', 'branding'));

        $stored = SiteContent::where(['section' => 'branding', 'field_key' => 'nav_logo'])->firstOrFail();
        Storage::disk('public')->assertExists($stored->value_en);
    }
}
