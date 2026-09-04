<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventCoverImageTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function eventPayload(array $overrides = []): array
    {
        return array_merge([
            'slug' => 'design-week',
            'name_ar' => 'أسبوع التصميم',
            'name_en' => 'Design Week',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-03',
            'status' => 'published',
        ], $overrides);
    }

    public function test_an_admin_can_upload_a_cover_image_for_an_event(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.events.store'), $this->eventPayload([
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
        ]))->assertRedirect(route('admin.events.index'));

        $event = Event::where('slug', 'design-week')->firstOrFail();
        $this->assertNotNull($event->cover_image_path);
        Storage::disk('public')->assertExists($event->cover_image_path);
    }

    public function test_an_event_saves_without_a_cover_image(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.events.store'), $this->eventPayload())
            ->assertRedirect(route('admin.events.index'));

        $this->assertNull(Event::where('slug', 'design-week')->firstOrFail()->cover_image_path);
    }

    public function test_saving_an_event_without_choosing_a_file_keeps_the_stored_cover(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create(['cover_image_path' => 'events/kept.jpg']);

        $this->actingAs($admin)->put(route('admin.events.update', $event), $this->eventPayload([
            'slug' => $event->slug,
        ]))->assertRedirect(route('admin.events.index'));

        $this->assertSame('events/kept.jpg', $event->fresh()->cover_image_path);
    }

    public function test_replacing_a_cover_image_deletes_the_previous_file(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        Storage::disk('public')->put('events/old.jpg', 'x');
        $event = Event::factory()->create(['cover_image_path' => 'events/old.jpg']);

        $this->actingAs($admin)->put(route('admin.events.update', $event), $this->eventPayload([
            'slug' => $event->slug,
            'cover_image' => UploadedFile::fake()->image('new.jpg'),
        ]));

        Storage::disk('public')->assertMissing('events/old.jpg');
        Storage::disk('public')->assertExists($event->fresh()->cover_image_path);
    }

    public function test_deleting_an_event_deletes_its_cover_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        Storage::disk('public')->put('events/gone.jpg', 'x');
        $event = Event::factory()->create(['cover_image_path' => 'events/gone.jpg']);

        $this->actingAs($admin)->delete(route('admin.events.destroy', $event));

        Storage::disk('public')->assertMissing('events/gone.jpg');
    }

    public function test_the_home_page_shows_the_cover_image_of_the_featured_event(): void
    {
        Event::factory()->create([
            'status' => 'published',
            'cover_image_path' => 'events/featured.jpg',
        ]);

        $this->get(route('home').'?lang=en')->assertSee('/storage/events/featured.jpg', false);
    }

    public function test_the_home_page_shows_cover_images_on_the_other_event_cards(): void
    {
        Event::factory()->create(['status' => 'published', 'start_date' => '2026-01-01', 'end_date' => '2026-01-02']);
        Event::factory()->create([
            'status' => 'published',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-02',
            'cover_image_path' => 'events/second.jpg',
        ]);

        $this->get(route('home').'?lang=en')->assertSee('/storage/events/second.jpg', false);
    }

    public function test_the_events_index_shows_the_cover_image(): void
    {
        Event::factory()->create(['status' => 'published', 'cover_image_path' => 'events/listed.jpg']);

        $this->get(route('events.index').'?lang=en')->assertSee('/storage/events/listed.jpg', false);
    }
}
