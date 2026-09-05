<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LandingPageSection;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LandingAboutImageTest extends TestCase
{
    use RefreshDatabase;

    private function event(): Event
    {
        $event = Event::factory()->create(['status' => 'published']);
        $event->landingPageContent()->create([
            'section' => LandingPageSection::About,
            'field_key' => 'body',
            'value_ar' => 'عن الحدث',
            'value_en' => 'About the event',
        ]);

        return $event;
    }

    private function withStoredImage(Event $event, string $path = 'landing/about/room.jpg'): Event
    {
        $event->landingPageContent()->create([
            'section' => LandingPageSection::About,
            'field_key' => 'image',
            'value_ar' => $path,
            'value_en' => $path,
        ]);

        return $event->fresh();
    }

    public function test_the_about_section_shows_its_image(): void
    {
        $event = $this->withStoredImage($this->event());

        $this->get(route('landing.show', $event).'?lang=en')
            ->assertSee('/storage/landing/about/room.jpg', false);
    }

    public function test_the_about_section_keeps_its_empty_panel_until_an_image_is_added(): void
    {
        $event = $this->event();

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('About the event');
        $response->assertDontSee('/storage/landing/about/', false);
    }

    public function test_an_admin_can_upload_the_about_image(): void
    {
        Storage::fake('public');
        $event = $this->event();

        $this->actingAs(User::factory()->create())
            ->put(route('admin.events.content.update', $event), [
                'about_image' => UploadedFile::fake()->image('room.jpg'),
                'visible_sections' => ['about'],
            ])
            ->assertRedirect(route('admin.events.content.edit', $event));

        $stored = $event->fresh()->contentFor(LandingPageSection::About, 'image');
        $this->assertNotNull($stored);
        Storage::disk('public')->assertExists($stored->value_en);
        $this->assertSame($stored->value_en, $stored->value_ar, 'A file is not translated, so both languages point at it.');
    }

    public function test_uploading_a_replacement_deletes_the_previous_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('landing/about/old.jpg', 'x');
        $event = $this->withStoredImage($this->event(), 'landing/about/old.jpg');

        $this->actingAs(User::factory()->create())
            ->put(route('admin.events.content.update', $event), [
                'about_image' => UploadedFile::fake()->image('new.jpg'),
            ]);

        Storage::disk('public')->assertMissing('landing/about/old.jpg');
        Storage::disk('public')->assertExists($event->fresh()->contentFor(LandingPageSection::About, 'image')->value_en);
    }

    public function test_an_admin_can_remove_the_about_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('landing/about/room.jpg', 'x');
        $event = $this->withStoredImage($this->event());

        $this->actingAs(User::factory()->create())
            ->put(route('admin.events.content.update', $event), ['remove_about_image' => '1']);

        $this->assertNull($event->fresh()->contentFor(LandingPageSection::About, 'image'));
        Storage::disk('public')->assertMissing('landing/about/room.jpg');
    }

    public function test_saving_the_page_without_touching_the_image_keeps_it(): void
    {
        $event = $this->withStoredImage($this->event());

        $this->actingAs(User::factory()->create())
            ->put(route('admin.events.content.update', $event), ['hero_headline_en' => 'Impact repeats']);

        $this->assertNotNull($event->fresh()->contentFor(LandingPageSection::About, 'image'));
    }

    public function test_the_admin_screen_offers_the_upload(): void
    {
        $event = $this->withStoredImage($this->event());

        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.events.content.edit', $event));

        $response->assertSee('name="about_image"', false);
        $response->assertSee('name="remove_about_image"', false);
        $response->assertSee('enctype="multipart/form-data"', false);
    }
}
