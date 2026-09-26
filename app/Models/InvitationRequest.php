<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvitationRequestStatus;
use Database\Factories\InvitationRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationRequest extends Model
{
    use HasFactory;

    protected $attributes = ['status' => 'pending'];

    protected $fillable = [
        'invitation_id', 'event_id', 'name', 'email', 'phone',
        'influencer_category_id', 'influencer_category_other',
        'instagram_url', 'instagram_followers', 'facebook_url', 'facebook_followers',
        'tiktok_url', 'tiktok_followers', 'status', 'ticket_id',
    ];

    protected function casts(): array
    {
        return ['status' => InvitationRequestStatus::class];
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function influencerCategory(): BelongsTo
    {
        return $this->belongsTo(InfluencerCategory::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    protected static function newFactory(): InvitationRequestFactory
    {
        return InvitationRequestFactory::new();
    }
}
