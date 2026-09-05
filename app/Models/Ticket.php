<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'ticket_type_id', 'discount_coupon_id', 'price', 'discount_amount',
        'name', 'email', 'phone', 'ticket_number',
        'status', 'ticket_id', 'workshop_booking_key', 'is_paid', 'payment_method', 'checked_in_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'is_paid' => 'boolean',
            'checked_in_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function discountCoupon(): BelongsTo
    {
        return $this->belongsTo(DiscountCoupon::class);
    }

    public function workshopBookings(): HasMany
    {
        return $this->hasMany(WorkshopBooking::class);
    }

    public function workshops(): BelongsToMany
    {
        return $this->belongsToMany(Workshop::class, 'workshop_bookings')->withTimestamps();
    }

    /**
     * How many workshops this ticket may book, from its ticket type.
     */
    public function workshopSlotAllowance(): int
    {
        return (int) ($this->ticketType?->workshop_slot_count ?? 0);
    }

    public function remainingWorkshopSlots(): int
    {
        return max(0, $this->workshopSlotAllowance() - $this->workshopBookings()->count());
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TicketRequestAnswer::class);
    }

    protected static function newFactory(): TicketFactory
    {
        return TicketFactory::new();
    }
}
