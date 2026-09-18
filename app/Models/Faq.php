<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\SanitizedRichText;
use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'question_ar', 'question_en', 'answer_ar', 'answer_en', 'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answer_ar' => SanitizedRichText::class,
            'answer_en' => SanitizedRichText::class,
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    protected static function newFactory(): FaqFactory
    {
        return FaqFactory::new();
    }
}
