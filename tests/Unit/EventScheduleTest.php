<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AgendaItem;
use App\Models\Event;
use App\Models\Workshop;
use App\Services\EventSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sessions_and_workshops_merge_per_day_in_time_order(): void
    {
        $event = Event::factory()->create();
        AgendaItem::factory()->for($event)->create(['day_date' => '2026-08-16', 'start_time' => '09:00', 'title_en' => 'Day two']);
        AgendaItem::factory()->for($event)->create(['day_date' => '2026-08-15', 'start_time' => '13:00', 'end_time' => '14:00', 'title_en' => 'Afternoon']);
        Workshop::factory()->for($event)->create(['day_date' => '2026-08-15', 'start_time' => '10:00', 'end_time' => '11:00', 'name_en' => 'Morning workshop']);

        app()->setLocale('en');
        $schedule = app(EventSchedule::class)->forEvent($event);

        $this->assertSame(['2026-08-15', '2026-08-16'], $schedule->keys()->all());
        $this->assertSame(['Morning workshop', 'Afternoon'], $schedule['2026-08-15']->map->title()->all());
        $this->assertSame(['workshop', 'session'], $schedule['2026-08-15']->map(fn ($entry) => $entry->kind)->all());
    }

    public function test_entries_at_the_same_time_list_sessions_before_workshops(): void
    {
        $event = Event::factory()->create();
        Workshop::factory()->for($event)->create(['day_date' => '2026-08-15', 'start_time' => '10:00', 'end_time' => '11:00']);
        AgendaItem::factory()->for($event)->create(['day_date' => '2026-08-15', 'start_time' => '10:00', 'end_time' => '11:00']);

        $kinds = app(EventSchedule::class)->forEvent($event)['2026-08-15']->map(fn ($entry) => $entry->kind)->all();

        $this->assertSame(['session', 'workshop'], $kinds);
    }

    public function test_unscheduled_workshops_stay_off_the_agenda(): void
    {
        $event = Event::factory()->create();
        Workshop::factory()->for($event)->unscheduled()->create();

        $this->assertTrue(app(EventSchedule::class)->forEvent($event)->isEmpty());
    }
}
