<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SafeSvgUploadTest extends TestCase
{
    use RefreshDatabase;

    private function svg(string $body): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'svg').'.svg';
        file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10">'.$body.'</svg>');

        return new UploadedFile($path, 'logo.svg', 'image/svg+xml', null, true);
    }

    public function test_a_plain_drawing_is_accepted(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->put(route('admin.site-content.update', 'branding'), [
                'images' => ['nav_logo' => $this->svg('<circle cx="5" cy="5" r="4"/>')],
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_a_drawing_carrying_a_script_is_refused(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->put(route('admin.site-content.update', 'branding'), [
                'images' => ['nav_logo' => $this->svg('<script>alert(1)</script>')],
            ])
            ->assertSessionHasErrors('images.nav_logo');

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_a_drawing_carrying_an_event_handler_is_refused(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->put(route('admin.site-content.update', 'branding'), [
                'images' => ['nav_logo' => $this->svg('<circle cx="5" cy="5" r="4" onload="alert(1)"/>')],
            ])
            ->assertSessionHasErrors('images.nav_logo');
    }

    public function test_an_event_logo_is_checked_the_same_way(): void
    {
        Storage::fake('public');
        $event = Event::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put(route('admin.events.update', $event), [
                'slug' => $event->slug,
                'name_ar' => $event->name_ar,
                'name_en' => $event->name_en,
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-02',
                'status' => 'published',
                'logo' => $this->svg('<foreignObject><iframe src="javascript:alert(1)"></iframe></foreignObject>'),
            ])
            ->assertSessionHasErrors('logo');

        $this->assertNull($event->fresh()->logo_path);
    }

    public function test_a_photograph_is_left_alone(): void
    {
        Storage::fake('public');
        $event = Event::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put(route('admin.events.update', $event), [
                'slug' => $event->slug,
                'name_ar' => $event->name_ar,
                'name_en' => $event->name_en,
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-02',
                'status' => 'published',
                'logo' => UploadedFile::fake()->image('logo.png'),
            ])
            ->assertSessionHasNoErrors();
    }
}
