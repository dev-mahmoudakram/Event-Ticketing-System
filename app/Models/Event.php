<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\LandingPageSection;
use App\Models\Concerns\ResolvesStoredMedia;
use App\Support\SocialPlatforms;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;
    use ResolvesStoredMedia;

    /** @var list<string> The landing page sections an admin can independently show or hide. Hero is not included — it always renders. */
    public const TOGGLEABLE_SECTIONS = [
        'about', 'stats', 'reel', 'speakers', 'workshops', 'tickets',
        'awards', 'gallery', 'testimonials', 'partners', 'faq', 'location', 'contact', 'newsletter',
    ];

    protected $fillable = [
        'slug', 'name_ar', 'name_en', 'tagline_ar', 'tagline_en', 'cover_image_path',
        'logo_path', 'footer_logo_path', 'favicon_path', 'apple_touch_icon_path', 'share_image_path',
        'contact_email', 'contact_phone', 'social_links',
        'start_date', 'end_date',
        'venue_name_ar', 'venue_name_en', 'venue_address_ar', 'venue_address_en',
        'map_embed_url', 'status', 'visible_sections',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => EventStatus::class,
        'visible_sections' => 'array',
        'social_links' => 'array',
    ];

    /**
     * The image that represents this event wherever it is listed, such as the event cards on
     * the Creators Hub landing page.
     */
    public function coverImageUrl(): ?string
    {
        return $this->storedMediaUrl($this->cover_image_path);
    }

    /**
     * The event's logo in the navigation bar, when it has been given one.
     */
    public function logoUrl(): ?string
    {
        return $this->storedMediaUrl($this->logo_path);
    }

    /**
     * The footer sits on a dark panel and usually needs a lighter version of the mark, so it
     * has its own slot and only falls back to the navigation logo when empty.
     */
    public function footerLogoUrl(): ?string
    {
        return $this->storedMediaUrl($this->footer_logo_path) ?? $this->logoUrl();
    }

    /**
     * The tab icon for this event's pages, when it has one of its own.
     */
    public function faviconUrl(): ?string
    {
        return $this->storedMediaUrl($this->favicon_path);
    }

    public function appleTouchIconUrl(): ?string
    {
        return $this->storedMediaUrl($this->apple_touch_icon_path);
    }

    /**
     * The picture a shared link shows. An event that has not been given one falls back to its
     * cover image, which is already chosen to represent it.
     */
    public function shareImageUrl(): ?string
    {
        return $this->storedMediaUrl($this->share_image_path) ?? $this->coverImageUrl();
    }

    /**
     * The event's own social accounts, in the platform's canonical order, without the blanks.
     *
     * @return array<string, string>
     */
    public function socialLinks(): array
    {
        return SocialPlatforms::filled($this->social_links ?? []);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function speakers(): HasMany
    {
        return $this->hasMany(Speaker::class)->orderBy('sort_order');
    }

    public function sponsors(): HasMany
    {
        return $this->hasMany(Sponsor::class)->orderBy('sort_order');
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class)->orderBy('sort_order');
    }

    public function workshops(): HasMany
    {
        return $this->hasMany(Workshop::class)->orderBy('sort_order');
    }

    public function agendaItems(): HasMany
    {
        return $this->hasMany(AgendaItem::class)->orderBy('day_date')->orderBy('start_time');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class)->orderBy('sort_order');
    }

    public function reels(): HasMany
    {
        return $this->hasMany(Reel::class)->orderBy('sort_order');
    }

    public function galleryPhotos(): HasMany
    {
        return $this->hasMany(GalleryPhoto::class)->orderBy('sort_order');
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class)->orderBy('sort_order');
    }

    public function contactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class)->latest();
    }

    public function newsletterSubscribers(): HasMany
    {
        return $this->hasMany(NewsletterSubscriber::class)->latest();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function ticketRequestFields(): HasMany
    {
        return $this->hasMany(TicketRequestField::class)->orderBy('sort_order');
    }

    public function landingPageContent(): HasMany
    {
        return $this->hasMany(LandingPageContent::class);
    }

    public function contentFor(LandingPageSection $section, string $fieldKey): ?LandingPageContent
    {
        return $this->landingPageContent
            ->first(fn (LandingPageContent $content) => $content->section === $section && $content->field_key === $fieldKey);
    }

    /**
     * Whether a toggleable landing page section should render for this event.
     * Defaults to true (visible) when the event has no explicit preference stored yet.
     */
    public function isSectionVisible(string $section): bool
    {
        return (bool) ($this->visible_sections[$section] ?? true);
    }

    protected static function newFactory(): EventFactory
    {
        return EventFactory::new();
    }
}
