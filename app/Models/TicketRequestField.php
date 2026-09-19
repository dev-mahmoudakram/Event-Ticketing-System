<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketRequestFieldType;
use Database\Factories\TicketRequestFieldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketRequestField extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'type', 'platform', 'label_ar', 'label_en', 'is_required', 'sort_order'];

    protected function casts(): array
    {
        return [
            'type' => TicketRequestFieldType::class,
            'is_required' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    protected static function newFactory(): TicketRequestFieldFactory
    {
        return TicketRequestFieldFactory::new();
    }
}
