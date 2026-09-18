<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\SanitizedRichText;
use Illuminate\Database\Eloquent\Model;

class SiteFaq extends Model
{
    protected $fillable = ['question_ar', 'question_en', 'answer_ar', 'answer_en', 'sort_order'];

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

    public function question(): string
    {
        return app()->getLocale() === 'ar' ? $this->question_ar : $this->question_en;
    }

    public function answer(): string
    {
        return app()->getLocale() === 'ar' ? $this->answer_ar : $this->answer_en;
    }
}
