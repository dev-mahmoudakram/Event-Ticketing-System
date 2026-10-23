<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketPaymentStatus;
use Database\Factories\TicketPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketPayment extends Model
{
    /** @use HasFactory<TicketPaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'ticket_id', 'sent_by', 'merchant_order_id', 'session_id', 'session_url',
        'amount', 'currency', 'status', 'transaction_id', 'expires_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketPaymentStatus::class,
            'amount' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
