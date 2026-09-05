<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\ContactMessage;
use App\Models\Event;
use App\Models\NewsletterSubscriber;
use App\Models\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The platform's own numbers, across every event Creators Hub has hosted.
 *
 * Where EventReport answers "how is this event doing", this answers "how is the platform
 * doing" — which events performed, whether the business is growing, and how many of the
 * people who paid actually walked through a door.
 */
class PlatformReport
{
    /**
     * How many months of history the growth chart covers.
     */
    private const MONTHS = 12;

    /**
     * The headline numbers, across everything.
     *
     * @return array{events: int, tickets: int, attendees: int, revenue: int, currency: string}
     */
    public function totals(): array
    {
        $paid = Ticket::query()->where('is_paid', true);

        return [
            'events' => Event::query()->count(),
            'tickets' => Ticket::query()->count(),
            'attendees' => Ticket::query()->whereNotNull('checked_in_at')->count(),
            'revenue' => (int) ((clone $paid)->sum('price') - (clone $paid)->sum('discount_amount')),
            'currency' => $this->currency(),
        ];
    }

    /**
     * Every event side by side, newest first, so one can be compared against another.
     *
     * @return Collection<int, array{name: string, requested: int, paid: int, arrived: int, revenue: int}>
     */
    public function byEvent(): Collection
    {
        return Event::query()
            ->orderByDesc('id')
            ->get()
            ->map(function (Event $event): array {
                $paid = $event->tickets()->where('is_paid', true);

                return [
                    'name' => app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en,
                    'requested' => $event->tickets()->count(),
                    'paid' => (clone $paid)->count(),
                    'arrived' => $event->tickets()->whereNotNull('checked_in_at')->count(),
                    'revenue' => (int) ((clone $paid)->sum('price') - (clone $paid)->sum('discount_amount')),
                ];
            });
    }

    /**
     * Requests, paid tickets and money by month.
     *
     * Every month in the window appears even when nothing happened in it, because a gap in a
     * line chart reads as missing data rather than as a quiet month.
     *
     * @return Collection<int, array{month: string, label: string, requested: int, paid: int, revenue: int}>
     */
    public function growth(): Collection
    {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths(self::MONTHS - 1);

        $requested = $this->monthlyTotals(Ticket::query()->where('created_at', '>=', $start), 'created_at');
        $paid = $this->monthlyTotals(
            Ticket::query()->where('is_paid', true)->where('created_at', '>=', $start),
            'created_at',
            money: true,
        );

        return collect(range(0, self::MONTHS - 1))->map(function (int $offset) use ($start, $requested, $paid): array {
            $month = $start->addMonths($offset);
            $key = $month->format('Y-m');

            return [
                'month' => $key,
                'label' => $month->translatedFormat('M Y'),
                'requested' => (int) ($requested[$key]['count'] ?? 0),
                'paid' => (int) ($paid[$key]['count'] ?? 0),
                'revenue' => (int) ($paid[$key]['revenue'] ?? 0),
            ];
        });
    }

    /**
     * Whether the people who were approved paid, and whether the people who paid turned up.
     *
     * @return array{approval_rate: int, payment_rate: int, show_up_rate: int, reviewed: int, approved: int, paid: int, arrived: int}
     */
    public function quality(): array
    {
        $reviewed = Ticket::query()->whereNotIn('status', [TicketStatus::Pending->value])->count();
        $approved = Ticket::query()->whereNotIn('status', [
            TicketStatus::Pending->value,
            TicketStatus::Rejected->value,
        ])->count();
        $paid = Ticket::query()->where('is_paid', true)->count();
        $arrived = Ticket::query()->whereNotNull('checked_in_at')->count();

        return [
            'reviewed' => $reviewed,
            'approved' => $approved,
            'paid' => $paid,
            'arrived' => $arrived,
            'approval_rate' => $this->rate($approved, $reviewed),
            'payment_rate' => $this->rate($paid, $approved),
            'show_up_rate' => $this->rate($arrived, $paid),
        ];
    }

    /**
     * Contact messages and newsletter sign-ups by month, across the platform.
     *
     * @return Collection<int, array{label: string, messages: int, subscribers: int}>
     */
    public function reach(): Collection
    {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths(self::MONTHS - 1);

        $messages = $this->monthlyTotals(ContactMessage::query()->where('created_at', '>=', $start), 'created_at');
        $subscribers = $this->monthlyTotals(NewsletterSubscriber::query()->where('created_at', '>=', $start), 'created_at');

        return collect(range(0, self::MONTHS - 1))->map(function (int $offset) use ($start, $messages, $subscribers): array {
            $month = $start->addMonths($offset);
            $key = $month->format('Y-m');

            return [
                'label' => $month->translatedFormat('M Y'),
                'messages' => (int) ($messages[$key]['count'] ?? 0),
                'subscribers' => (int) ($subscribers[$key]['count'] ?? 0),
            ];
        });
    }

    /**
     * Group a query into months keyed 'YYYY-MM'.
     *
     * Grouping happens in PHP rather than SQL because the date functions that would do it in
     * the database differ between MySQL and the SQLite the tests run on.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return Collection<string, array{count: int, revenue: int}>
     */
    private function monthlyTotals($query, string $column, bool $money = false): Collection
    {
        $columns = $money ? [$column, 'price', 'discount_amount'] : [$column];

        return $query->get($columns)
            ->groupBy(fn ($row) => $row->{$column}->format('Y-m'))
            ->map(fn (Collection $rows): array => [
                'count' => $rows->count(),
                'revenue' => $money ? (int) ($rows->sum('price') - $rows->sum('discount_amount')) : 0,
            ]);
    }

    private function rate(int $part, int $whole): int
    {
        return $whole === 0 ? 0 : (int) round($part / $whole * 100);
    }

    private function currency(): string
    {
        return (string) (Event::query()->with('ticketTypes')->get()
            ->flatMap->ticketTypes->first()->currency ?? '');
    }
}
