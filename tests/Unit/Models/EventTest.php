<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_uses_slug_as_route_key(): void
    {
        $event = Event::factory()->create(['slug' => 'ccs-2026']);

        $this->assertSame('slug', $event->getRouteKeyName());
        $this->assertSame('ccs-2026', $event->getRouteKey());
    }

    public function test_event_has_bilingual_name_fields(): void
    {
        $event = Event::factory()->create([
            'name_ar' => 'قمة صناع المحتوى',
            'name_en' => 'Content Creators Summit',
        ]);

        $this->assertSame('قمة صناع المحتوى', $event->name_ar);
        $this->assertSame('Content Creators Summit', $event->name_en);
    }

    public function test_event_status_casts_to_enum(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);

        $this->assertSame(EventStatus::Published, $event->status);
    }

    public function test_a_one_day_event_is_a_single_day(): void
    {
        $event = Event::factory()->create([
            'start_date' => '2026-12-26',
            'end_date' => '2026-12-26',
        ]);

        $this->assertTrue($event->isSingleDay());
    }

    public function test_a_multi_day_event_is_not_a_single_day(): void
    {
        $event = Event::factory()->create([
            'start_date' => '2026-12-26',
            'end_date' => '2026-12-28',
        ]);

        $this->assertFalse($event->isSingleDay());
    }

    public function test_check_in_is_closed_before_the_event_starts(): void
    {
        $event = Event::factory()->create([
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
        ]);

        $this->assertFalse($event->checkInIsOpen());
    }

    public function test_check_in_is_closed_after_the_event_ends(): void
    {
        $event = Event::factory()->create([
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $this->assertFalse($event->checkInIsOpen());
    }

    public function test_check_in_is_open_on_any_day_of_a_multi_day_event(): void
    {
        $event = Event::factory()->create([
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'check_in_starts_at' => null,
        ]);

        $this->assertTrue($event->checkInIsOpen());
    }

    public function test_check_in_is_open_all_day_when_no_opening_time_is_set(): void
    {
        $event = Event::factory()->create([
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'check_in_starts_at' => null,
        ]);

        $this->assertTrue($event->checkInIsOpen());
    }

    public function test_check_in_is_closed_before_the_configured_opening_time(): void
    {
        $event = Event::factory()->create([
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'check_in_starts_at' => now()->addHour()->format('H:i:s'),
        ]);

        $this->assertFalse($event->checkInIsOpen());
    }

    public function test_check_in_is_open_after_the_configured_opening_time(): void
    {
        $event = Event::factory()->create([
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'check_in_starts_at' => now()->subHour()->format('H:i:s'),
        ]);

        $this->assertTrue($event->checkInIsOpen());
    }
}
