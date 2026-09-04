<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Models\SiteContent;
use App\Models\User;
use App\Support\SiteText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeroSliderTest extends TestCase
{
    use RefreshDatabase;

    private function slide(array $attributes = []): HeroSlide
    {
        return HeroSlide::create(array_merge([
            'image_path' => 'hero-slides/one.jpg',
            'sort_order' => 0,
        ], $attributes));
    }

    public function test_a_single_slide_shows_its_image_without_dots(): void
    {
        $this->slide();

        $response = $this->get(route('home').'?lang=en');

        $response->assertOk();
        $response->assertSee('/storage/hero-slides/one.jpg', false);
        // Nothing to navigate between, so the dots stay out of the markup.
        $response->assertDontSee('aria-label="Choose a slide"', false);
    }

    public function test_multiple_slides_get_navigation_dots(): void
    {
        $this->slide(['sort_order' => 0]);
        $this->slide(['image_path' => 'hero-slides/two.jpg', 'sort_order' => 1]);

        $response = $this->get(route('home').'?lang=en');

        $response->assertSee('/storage/hero-slides/one.jpg', false);
        $response->assertSee('/storage/hero-slides/two.jpg', false);
        $response->assertSee('Choose a slide');
        $response->assertSee('Slide 1');
        $response->assertSee('Slide 2');
    }

    public function test_slides_render_in_sort_order(): void
    {
        $this->slide(['image_path' => 'hero-slides/second.jpg', 'sort_order' => 2]);
        $this->slide(['image_path' => 'hero-slides/first.jpg', 'sort_order' => 1]);

        $html = $this->get(route('home').'?lang=en')->getContent();

        $this->assertLessThan(
            strpos($html, 'hero-slides/second.jpg'),
            strpos($html, 'hero-slides/first.jpg'),
        );
    }

    public function test_a_slide_without_its_own_words_inherits_the_hero_copy(): void
    {
        SiteContent::create([
            'section' => 'hero',
            'field_key' => 'headline',
            'value_en' => 'Shared hero headline',
            'value_ar' => 'عنوان مشترك',
        ]);
        SiteText::flush();
        $this->slide();

        $this->get(route('home').'?lang=en')->assertSee('Shared hero headline');
    }

    public function test_a_slide_can_override_the_hero_copy(): void
    {
        $this->slide(['headline_en' => 'This slide speaks for itself', 'body_en' => 'With its own text.']);

        $response = $this->get(route('home').'?lang=en');

        $response->assertSee('This slide speaks for itself');
        $response->assertSee('With its own text.');
    }

    public function test_the_hero_falls_back_to_site_content_when_there_are_no_slides(): void
    {
        $response = $this->get(route('home').'?lang=en');

        $response->assertOk();
        $response->assertDontSee('aria-label="Choose a slide"', false);
        // The registry default still renders, so the hero is never blank.
        $response->assertSee('Where the people who design and build Egypt actually meet.');
    }

    public function test_an_admin_can_upload_a_slide(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.hero-slides.store'), [
            'image' => UploadedFile::fake()->image('banner.jpg'),
            'headline_en' => 'Opening night',
            'sort_order' => 0,
        ]);

        $response->assertRedirect(route('admin.hero-slides.index'));
        $slide = HeroSlide::where('headline_en', 'Opening night')->firstOrFail();
        Storage::disk('public')->assertExists($slide->image_path);
    }

    public function test_creating_a_slide_requires_an_image(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.hero-slides.store'), ['headline_en' => 'No picture'])
            ->assertSessionHasErrors(['image']);
    }

    public function test_deleting_a_slide_removes_its_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        Storage::disk('public')->put('hero-slides/gone.jpg', 'x');
        $slide = $this->slide(['image_path' => 'hero-slides/gone.jpg']);

        $this->actingAs($admin)->delete(route('admin.hero-slides.destroy', $slide));

        $this->assertDatabaseMissing('hero_slides', ['id' => $slide->id]);
        Storage::disk('public')->assertMissing('hero-slides/gone.jpg');
    }

    public function test_guests_cannot_manage_slides(): void
    {
        $this->get(route('admin.hero-slides.index'))->assertRedirect(route('admin.login'));
    }
}
