<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AwardVoteCode extends Model
{
    protected $fillable = [
        'award_id', 'award_nominee_id', 'email', 'code_hash', 'attempts', 'expires_at', 'ip_address',
    ];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'attempts' => 'integer'];
    }

    public function award(): BelongsTo
    {
        return $this->belongsTo(Award::class);
    }

    public function nominee(): BelongsTo
    {
        return $this->belongsTo(AwardNominee::class, 'award_nominee_id');
    }
}
