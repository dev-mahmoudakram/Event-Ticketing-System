<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TicketRequestAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketRequestAnswer extends Model
{
    use HasFactory;

    protected $fillable = ['ticket_id', 'ticket_request_field_id', 'value', 'follower_count', 'file_path'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(TicketRequestField::class, 'ticket_request_field_id');
    }

    protected static function newFactory(): TicketRequestAnswerFactory
    {
        return TicketRequestAnswerFactory::new();
    }
}
