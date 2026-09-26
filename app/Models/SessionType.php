<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SessionTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A kind of agenda session ("Keynote", "Fireside chat"), per event and bilingual. Shown as the
 * pill on each agenda card; a break type renders as a slim line instead of a card.
 */
class SessionType extends Model
{
    /** @use HasFactory<SessionTypeFactory> */
    use HasFactory;

    /** @var list<array{name_ar: string, name_en: string, is_break: bool}> */
    public const DEFAULTS = [
        ['name_ar' => 'كلمة رئيسية', 'name_en' => 'Keynote', 'is_break' => false],
        ['name_ar' => 'جلسة', 'name_en' => 'Session', 'is_break' => false],
        ['name_ar' => 'ورشة عمل', 'name_en' => 'Workshop', 'is_break' => false],
        ['name_ar' => 'استراحة', 'name_en' => 'Break', 'is_break' => true],
        ['name_ar' => 'حلقة نقاشية', 'name_en' => 'Panel', 'is_break' => false],
    ];

    protected $fillable = ['event_id', 'name_ar', 'name_en', 'is_break', 'sort_order'];

    protected function casts(): array
    {
        return ['is_break' => 'boolean'];
    }

    public static function seedDefaultsFor(Event $event): void
    {
        foreach (self::DEFAULTS as $position => $type) {
            $event->sessionTypes()->create($type + ['sort_order' => $position]);
        }
    }

    public function name(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function agendaItems(): HasMany
    {
        return $this->hasMany(AgendaItem::class);
    }
}
