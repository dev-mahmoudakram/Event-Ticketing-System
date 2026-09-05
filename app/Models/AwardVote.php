<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AwardVote extends Model
{
    protected $fillable = [
        'event_id', 'award_id', 'category_en', 'email',
        'confirmation_token', 'confirmed_at', 'ip_address',
    ];

    protected $hidden = ['confirmation_token', 'email', 'ip_address'];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function award(): BelongsTo
    {
        return $this->belongsTo(Award::class);
    }

    /**
     * Only confirmed votes count. An unconfirmed row is somebody who started voting and never
     * clicked the link in their email.
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->whereNotNull('confirmed_at');
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }
}
