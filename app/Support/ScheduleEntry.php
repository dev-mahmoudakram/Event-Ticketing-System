<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\AgendaItem;
use App\Models\Speaker;
use App\Models\Workshop;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * One card on the public schedule: an agenda session or a workshop, read the same way by the
 * card, the pop-up and the filters.
 */
final class ScheduleEntry
{
    public function __construct(
        public readonly string $kind,
        public readonly AgendaItem|Workshop $model,
    ) {}

    public static function forSession(AgendaItem $item): self
    {
        return new self('session', $item);
    }

    public static function forWorkshop(Workshop $workshop): self
    {
        return new self('workshop', $workshop);
    }

    public function anchor(): string
    {
        return $this->kind.'-'.$this->model->id;
    }

    public function title(): string
    {
        return $this->model instanceof AgendaItem ? $this->model->title() : $this->model->name();
    }

    public function description(): ?string
    {
        return $this->model->description();
    }

    public function typeLabel(): string
    {
        return $this->model instanceof AgendaItem ? $this->model->sessionType->name() : __('Workshop');
    }

    public function typeKey(): string
    {
        return $this->model instanceof AgendaItem ? 't'.$this->model->session_type_id : 'workshop';
    }

    public function isBreak(): bool
    {
        return $this->model instanceof AgendaItem && $this->model->sessionType->is_break;
    }

    public function day(): CarbonInterface
    {
        return $this->model->day_date;
    }

    public function start(): string
    {
        return $this->model->start_time->format('H:i');
    }

    public function end(): string
    {
        return $this->model->end_time->format('H:i');
    }

    public function locationName(): ?string
    {
        return $this->model->location?->name();
    }

    /** @return Collection<int, Speaker> */
    public function speakers(): Collection
    {
        return $this->model->speakers;
    }

    public function workshop(): ?Workshop
    {
        return $this->model instanceof Workshop ? $this->model : null;
    }
}
