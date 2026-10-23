<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AwardVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'award_id', 'award_nominee_id', 'email', 'ip_address', 'removed_at', 'removed_by',
    ];

    protected $hidden = ['email', 'ip_address'];

    protected function casts(): array
    {
        return ['removed_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function award(): BelongsTo
    {
        return $this->belongsTo(Award::class);
    }

    public function nominee(): BelongsTo
    {
        return $this->belongsTo(AwardNominee::class, 'award_nominee_id');
    }

    /**
     * Removed votes keep their row, so the email stays blocked, but they no longer count.
     */
    public function scopeCounted(Builder $query): Builder
    {
        return $query->whereNull('removed_at');
    }

    public function isRemoved(): bool
    {
        return $this->removed_at !== null;
    }
}
