<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvitationStatus;
use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invitation extends Model
{
    use HasFactory;

    protected $attributes = ['status' => 'unused'];

    protected $fillable = ['event_id', 'ticket_type_id', 'token', 'otp', 'status', 'expires_at'];

    protected function casts(): array
    {
        return ['status' => InvitationStatus::class, 'expires_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function invitationRequest(): HasOne
    {
        return $this->hasOne(InvitationRequest::class);
    }

    public function isUsable(): bool
    {
        return $this->status === InvitationStatus::Unused && $this->expires_at->isFuture();
    }

    protected static function newFactory(): InvitationFactory
    {
        return InvitationFactory::new();
    }
}
