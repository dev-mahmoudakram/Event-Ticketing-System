<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\LandingPageSection;
use App\Http\Controllers\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LandingPageContentRequest;
use App\Models\Event;
use App\Support\UploadLimit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LandingPageContentController extends Controller
{
    use HandlesMediaUploads;

    /** @var array<string, array{section: LandingPageSection, field_key: string}> */
    private const FIELDS = [
        'hero_headline' => ['section' => LandingPageSection::Hero, 'field_key' => 'headline'],
        'about_body' => ['section' => LandingPageSection::About, 'field_key' => 'body'],
        'location_intro' => ['section' => LandingPageSection::Location, 'field_key' => 'intro'],
        'awards_teaser_blurb' => ['section' => LandingPageSection::AwardsTeaser, 'field_key' => 'blurb'],
        'stats_attendees_count' => ['section' => LandingPageSection::Stats, 'field_key' => 'attendees_count'],
        'stats_countries_count' => ['section' => LandingPageSection::Stats, 'field_key' => 'countries_count'],
    ];

    public function edit(Event $event): View
    {
        $values = [];
        foreach (self::FIELDS as $prefix => $target) {
            $content = $event->contentFor($target['section'], $target['field_key']);
            $values[$prefix.'_ar'] = $content?->value_ar;
            $values[$prefix.'_en'] = $content?->value_en;
        }

        $visibleSections = array_values(array_filter(
            Event::TOGGLEABLE_SECTIONS,
            fn (string $section) => $event->isSectionVisible($section),
        ));

        return view('admin.landing-page-content.edit', [
            'event' => $event,
            'values' => $values,
            'aboutImage' => $event->contentFor(LandingPageSection::About, 'image')?->mediaUrl(),
            'uploadLimit' => UploadLimit::label(UploadLimit::effectiveKilobytes((int) config('media.max_image_kb'))),
            'sections' => Event::TOGGLEABLE_SECTIONS,
            'visibleSections' => $visibleSections,
        ]);
    }

    public function update(LandingPageContentRequest $request, Event $event): RedirectResponse
    {
        $data = $request->validated();

        foreach (self::FIELDS as $prefix => $target) {
            $event->landingPageContent()->updateOrCreate(
                ['section' => $target['section'], 'field_key' => $target['field_key']],
                ['value_ar' => $data[$prefix.'_ar'] ?? null, 'value_en' => $data[$prefix.'_en'] ?? null],
            );
        }

        $this->saveAboutImage($request, $event);

        $checkedSections = $data['visible_sections'] ?? [];
        $visibleSections = [];
        foreach (Event::TOGGLEABLE_SECTIONS as $section) {
            $visibleSections[$section] = in_array($section, $checkedSections, true);
        }
        $event->update(['visible_sections' => $visibleSections]);

        return redirect()->route('admin.events.content.edit', $event);
    }

    /**
     * Add, replace or remove the picture beside the About text.
     *
     * It lives in the same content table as the rest of the section, keyed 'image', with the
     * stored path in both languages because a file is not translated.
     */
    private function saveAboutImage(LandingPageContentRequest $request, Event $event): void
    {
        $stored = $event->contentFor(LandingPageSection::About, 'image');

        if ($request->boolean('remove_about_image')) {
            $this->deleteStoredMedia($stored?->value_en);
            $stored?->delete();

            return;
        }

        $path = $this->storeUploadedMedia($request, 'about_image', 'landing/about');

        if ($path === null) {
            return;
        }

        $this->deleteStoredMedia($stored?->value_en);

        $event->landingPageContent()->updateOrCreate(
            ['section' => LandingPageSection::About, 'field_key' => 'image'],
            ['value_ar' => $path, 'value_en' => $path],
        );
    }
}
