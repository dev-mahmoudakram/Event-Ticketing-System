<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\SanitizedRichText;
use App\Enums\RequiredPage;
use App\Support\PolicyDrafts;
use Database\Factories\EventPageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A page that belongs to one event: one of the six required policy pages, or a custom one.
 */
class EventPage extends Model
{
    /** @use HasFactory<EventPageFactory> */
    use HasFactory;

    /** A [bracketed placeholder] left in a starter draft. */
    public const PLACEHOLDER_PATTERN = '/\[[^\]\n]{2,80}\]/u';

    protected $fillable = [
        'event_id', 'key', 'slug', 'title_ar', 'title_en', 'body_ar', 'body_en',
        'show_in_footer', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'body_ar' => SanitizedRichText::class,
            'body_en' => SanitizedRichText::class,
            'show_in_footer' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Creates whichever required pages the event doesn't have yet, with starter drafts.
     */
    public static function seedRequiredFor(Event $event): void
    {
        $existing = $event->pages()->whereNotNull('key')->pluck('key')->all();

        foreach (RequiredPage::cases() as $page) {
            if (in_array($page->value, $existing, true)) {
                continue;
            }

            $event->pages()->create([
                'key' => $page->value,
                'slug' => $page->slug(),
                'title_ar' => $page->titleAr(),
                'title_en' => $page->titleEn(),
                'body_ar' => PolicyDrafts::render($page, 'ar', PolicyDrafts::eventData($event, 'ar')),
                'body_en' => PolicyDrafts::render($page, 'en', PolicyDrafts::eventData($event, 'en')),
                'show_in_footer' => true,
                'is_published' => true,
                'sort_order' => $page->sortOrder(),
            ]);
        }
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function requiredPage(): ?RequiredPage
    {
        return $this->key !== null ? RequiredPage::tryFrom($this->key) : null;
    }

    public function isRequired(): bool
    {
        return $this->requiredPage() !== null;
    }

    public function title(): string
    {
        return app()->getLocale() === 'ar' ? $this->title_ar : $this->title_en;
    }

    public function body(): ?string
    {
        return app()->getLocale() === 'ar' ? $this->body_ar : $this->body_en;
    }

    /**
     * Still has a [placeholder] from the starter draft in either language.
     */
    public function needsDetails(): bool
    {
        return preg_match(self::PLACEHOLDER_PATTERN, (string) $this->body_ar.' '.$this->body_en) === 1;
    }
}
