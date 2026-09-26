<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Support\ScheduleEntry;
use Illuminate\Support\Collection;

/**
 * The public schedule: agenda sessions and scheduled workshops, merged into one list per day.
 * Workshops without a day and times aren't on it (they still show on the Workshops page).
 */
class EventSchedule
{
    /**
     * @return Collection<string, Collection<int, ScheduleEntry>> keyed by Y-m-d, days ascending
     */
    public function forEvent(Event $event): Collection
    {
        $sessions = $event->agendaItems()->with(['speakers', 'sessionType', 'location'])->get()
            ->map(fn ($item) => ScheduleEntry::forSession($item));

        $workshops = $event->workshops()
            ->whereNotNull('day_date')->whereNotNull('start_time')->whereNotNull('end_time')
            ->with(['speakers', 'location'])->withCount('bookings')->get()
            ->map(fn ($workshop) => ScheduleEntry::forWorkshop($workshop));

        return $sessions->concat($workshops)
            ->sortBy([
                fn (ScheduleEntry $a, ScheduleEntry $b) => $a->day()->toDateString() <=> $b->day()->toDateString(),
                fn (ScheduleEntry $a, ScheduleEntry $b) => $a->start() <=> $b->start(),
                fn (ScheduleEntry $a, ScheduleEntry $b) => ($a->kind === 'session' ? 0 : 1) <=> ($b->kind === 'session' ? 0 : 1),
                fn (ScheduleEntry $a, ScheduleEntry $b) => $a->model->id <=> $b->model->id,
            ])
            ->groupBy(fn (ScheduleEntry $entry) => $entry->day()->toDateString())
            ->map(fn (Collection $day) => $day->values());
    }
}
