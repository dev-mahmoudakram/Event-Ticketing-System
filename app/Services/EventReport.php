<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Event;
use Illuminate\Support\Collection;

class EventReport
{
    public function __construct(private readonly Event $event) {}

    /**
     * How many tickets sit in each state of the workflow.
     *
     * Every status is listed even at zero, because "nobody has been rejected yet" is a real
     * answer and a missing row reads as a broken report.
     *
     * @return Collection<int, array{status: TicketStatus, label: string, count: int}>
     */
    public function ticketsByStatus(): Collection
    {
        $counts = $this->event->tickets()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(TicketStatus::cases())->map(fn (TicketStatus $status) => [
            'status' => $status,
            'label' => $status->label(),
            'count' => (int) ($counts[$status->value] ?? 0),
        ]);
    }

    /**
     * The funnel, as counts rather than percentages of percentages.
     *
     * @return array{requested: int, approved: int, paid: int, checked_in: int}
     */
    public function funnel(): array
    {
        $tickets = $this->event->tickets();

        return [
            'requested' => (clone $tickets)->count(),
            // Approved means it got past review, whatever happened after.
            'approved' => (clone $tickets)->whereNotIn('status', [
                TicketStatus::Pending->value,
                TicketStatus::Rejected->value,
            ])->count(),
            'paid' => (clone $tickets)->where('is_paid', true)->count(),
            'checked_in' => (clone $tickets)->whereNotNull('checked_in_at')->count(),
        ];
    }

    /**
     * Money, from the prices stored on the tickets themselves.
     *
     * Tickets requested before prices were recorded have a null price; they count as zero
     * rather than being guessed at from their ticket type's price today.
     *
     * @return array{currency: string, collected: int, discounted: int, outstanding: int}
     */
    public function revenue(): array
    {
        $paid = $this->event->tickets()->where('is_paid', true);
        $awaiting = $this->event->tickets()
            ->where('is_paid', false)
            ->where('status', TicketStatus::PaymentPending->value);

        return [
            'currency' => (string) ($this->event->ticketTypes->first()->currency ?? ''),
            'collected' => (int) ((clone $paid)->sum('price') - (clone $paid)->sum('discount_amount')),
            'discounted' => (int) (clone $paid)->sum('discount_amount'),
            'outstanding' => (int) ($awaiting->sum('price') - (clone $awaiting)->sum('discount_amount')),
        ];
    }

    /**
     * What each ticket type sold.
     *
     * @return Collection<int, array{name: string, sold: int, revenue: int}>
     */
    public function revenueByTicketType(): Collection
    {
        return $this->event->ticketTypes->map(function ($type) {
            $sold = $this->event->tickets()->where('ticket_type_id', $type->id)->where('is_paid', true);

            return [
                'name' => app()->getLocale() === 'ar' ? $type->name_ar : $type->name_en,
                'sold' => (clone $sold)->count(),
                'revenue' => (int) ((clone $sold)->sum('price') - (clone $sold)->sum('discount_amount')),
            ];
        });
    }

    /**
     * Arrivals on the day, by hour, so the door can be staffed.
     *
     * @return array{issued: int, arrived: int, by_hour: Collection<int, array{hour: string, count: int}>}
     */
    public function checkIns(): array
    {
        $arrived = $this->event->tickets()->whereNotNull('checked_in_at')->get(['checked_in_at']);

        return [
            'issued' => $this->event->tickets()->where('is_paid', true)->count(),
            'arrived' => $arrived->count(),
            'by_hour' => $arrived
                ->groupBy(fn ($ticket) => $ticket->checked_in_at->format('Y-m-d H:00'))
                ->map(fn ($group, $hour) => ['hour' => $hour, 'count' => $group->count()])
                ->values(),
        ];
    }

    /**
     * How often each coupon was actually used on a ticket.
     *
     * @return Collection<int, array{code: string, used: int, discounted: int}>
     */
    public function coupons(): Collection
    {
        return $this->event->discountCoupons->map(function ($coupon) {
            $tickets = $this->event->tickets()->where('discount_coupon_id', $coupon->id);

            return [
                'code' => $coupon->code,
                'used' => (clone $tickets)->count(),
                'discounted' => (int) (clone $tickets)->sum('discount_amount'),
            ];
        })->filter(fn (array $row) => $row['used'] > 0)->values();
    }

    /**
     * How full each workshop is.
     *
     * @return Collection<int, array{name: string, booked: int, capacity: int, remaining: int|null}>
     */
    public function workshops(): Collection
    {
        return $this->event->workshops()->withCount('bookings')->get()->map(fn ($workshop) => [
            'name' => $workshop->name(),
            'booked' => $workshop->bookings_count,
            'capacity' => (int) $workshop->capacity,
            'remaining' => $workshop->remainingCapacity(),
        ]);
    }

    /**
     * The attendee list, as rows ready to be written to a spreadsheet.
     *
     * @return Collection<int, array<string, string|int>>
     */
    public function attendeeRows(): Collection
    {
        return $this->event->tickets()
            ->with('ticketType', 'discountCoupon', 'workshops')
            ->orderBy('id')
            ->get()
            ->map(fn ($ticket) => [
                'reference' => (string) $ticket->ticket_number,
                'name' => $ticket->name,
                'email' => $ticket->email,
                'phone' => $ticket->phone,
                'ticket_type' => (string) ($ticket->ticketType?->name_en ?? ''),
                'status' => $ticket->status->value,
                'price' => (int) $ticket->price,
                'discount' => (int) $ticket->discount_amount,
                'coupon' => (string) ($ticket->discountCoupon?->code ?? ''),
                'paid' => $ticket->is_paid ? 'yes' : 'no',
                'workshops' => $ticket->workshops->map(fn ($workshop) => $workshop->name_en)->implode(' | '),
                'checked_in_at' => $ticket->checked_in_at?->toDateTimeString() ?? '',
            ]);
    }
}
