<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TestimonialCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_testimonial(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.testimonials.store', $event), [
            'quote_ar' => 'حدث رائع', 'quote_en' => 'A great event',
            'name_ar' => 'سارة', 'name_en' => 'Sarah',
            'title_ar' => 'مؤسسة', 'title_en' => 'Founder', 'sort_order' => 0,
        ]);

        $response->assertRedirect(route('admin.events.testimonials.index', $event));
        // The rich-text cast wraps a plain quote in a paragraph on save — see
        // App\Casts\SanitizedRichText, applied to Testimonial::quote_ar/quote_en.
        $this->assertDatabaseHas('testimonials', ['event_id' => $event->id, 'quote_en' => '<p>A great event</p>']);
    }

    /**
     * The quote field uses CKEditor (App\Casts\SanitizedRichText on Testimonial::quote_ar/
     * quote_en), so it must be sanitized on save exactly like every other rich-text field in
     * the app, and safe to render unescaped on the public landing page.
     */
    public function test_testimonial_quote_is_sanitized_on_save(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($admin)->post(route('admin.events.testimonials.store', $event), [
            'quote_ar' => 'حدث رائع', 'quote_en' => '<p>Safe</p><script>alert(1)</script>',
            'name_ar' => 'سارة', 'name_en' => 'Sarah',
            'title_ar' => 'مؤسسة', 'title_en' => 'Founder', 'sort_order' => 0,
        ]);

        $this->assertDatabaseHas('testimonials', [
            'event_id' => $event->id, 'quote_en' => '<p>Safe</p>',
        ]);
    }

    public function test_creating_a_testimonial_requires_bilingual_quote(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.testimonials.store', $event), [
            'quote_ar' => '', 'quote_en' => '', 'name_ar' => 'سارة', 'name_en' => 'Sarah', 'title_ar' => 'مؤسسة', 'title_en' => 'Founder',
        ]);

        $response->assertSessionHasErrors(['quote_ar', 'quote_en']);
    }

    public function test_admin_can_upload_a_photo_for_a_testimonial(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.testimonials.store', $event), [
            'quote_ar' => 'حدث رائع', 'quote_en' => 'A great event',
            'name_ar' => 'سارة', 'name_en' => 'Sarah',
            'title_ar' => 'مؤسسة', 'title_en' => 'Founder', 'sort_order' => 0,
            'photo' => UploadedFile::fake()->image('sarah.jpg'),
        ]);

        $response->assertRedirect(route('admin.events.testimonials.index', $event));
        $testimonial = Testimonial::where('event_id', $event->id)->firstOrFail();
        $this->assertNotNull($testimonial->photo_path);
        Storage::disk('public')->assertExists($testimonial->photo_path);
    }

    public function test_replacing_a_photo_deletes_the_old_one(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $testimonial = Testimonial::factory()->for($event)->create(['photo_path' => 'testimonials/old.jpg']);
        Storage::disk('public')->put('testimonials/old.jpg', 'fake');

        $this->actingAs($admin)->put(route('admin.events.testimonials.update', [$event, $testimonial]), [
            'quote_ar' => $testimonial->quote_ar, 'quote_en' => $testimonial->quote_en,
            'name_ar' => $testimonial->name_ar, 'name_en' => $testimonial->name_en,
            'title_ar' => $testimonial->title_ar, 'title_en' => $testimonial->title_en,
            'photo' => UploadedFile::fake()->image('new.jpg'),
        ]);

        Storage::disk('public')->assertMissing('testimonials/old.jpg');
        $this->assertNotSame('testimonials/old.jpg', $testimonial->fresh()->photo_path);
    }

    public function test_admin_can_delete_a_testimonial(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $testimonial = Testimonial::factory()->for($event)->create();

        $response = $this->actingAs($admin)->delete(route('admin.events.testimonials.destroy', [$event, $testimonial]));

        $response->assertRedirect(route('admin.events.testimonials.index', $event));
        $this->assertDatabaseMissing('testimonials', ['id' => $testimonial->id]);
    }

    public function test_admin_can_view_the_index_page_with_records(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        Testimonial::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.testimonials.index', $event));

        $response->assertOk();
    }

    public function test_admin_can_view_the_edit_page(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $testimonial = Testimonial::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.testimonials.edit', [$event, $testimonial]));

        $response->assertOk();
    }
}
