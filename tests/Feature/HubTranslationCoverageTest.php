<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AgendaItemType;
use App\Models\Event;
use App\Support\SiteContentRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HubTranslationCoverageTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function arabicStrings(): array
    {
        return json_decode((string) file_get_contents(base_path('lang/ar.json')), true);
    }

    /**
     * Every key actually translated, not just present — a key mapped to itself renders as
     * English on the Arabic page just as surely as a missing key does.
     *
     * @param  list<string>  $keys
     */
    private function assertAllTranslated(array $keys): void
    {
        $arabic = $this->arabicStrings();
        $untranslated = array_values(array_filter(
            $keys,
            fn (string $key) => ! array_key_exists($key, $arabic) || $arabic[$key] === $key,
        ));

        $this->assertSame([], $untranslated, 'These strings would render in English on the Arabic page: '.implode(', ', $untranslated));
    }

    public function test_every_default_on_the_landing_page_has_an_arabic_translation(): void
    {
        $arabic = $this->arabicStrings();
        $untranslated = [];

        foreach (SiteContentRegistry::sections() as $section => $definition) {
            foreach ($definition['fields'] as $field => $meta) {
                $default = $meta['default'] ?? null;

                if ($default === null) {
                    continue;
                }

                if (! array_key_exists($default, $arabic) || $arabic[$default] === $default) {
                    $untranslated[] = $section.'.'.$field;
                }
            }
        }

        $this->assertSame([], $untranslated, 'These landing page defaults would render in English on the Arabic page: '.implode(', ', $untranslated));
    }

    public function test_event_dates_are_written_in_the_reading_language(): void
    {
        Event::factory()->create([
            'status' => 'published',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-16',
        ]);

        $this->get(route('home').'?lang=ar')->assertSee('أغسطس');
        $this->get(route('home').'?lang=en')->assertSee('Aug');
    }

    /**
     * The registration desk's four outcomes are read off a match() in TicketCheckIn::message()
     * rather than written inline in a view, so a coverage sweep over the Blade files alone
     * would miss them entirely.
     */
    public function test_every_check_in_outcome_has_an_arabic_translation(): void
    {
        $this->assertAllTranslated([
            'Ticket verified. Entry allowed.',
            'This ticket has already been used. Entry denied.',
            'Invalid or unpaid ticket. Entry denied.',
            'Check-in has not opened yet. Entry denied.',
            'Invalid QR code. Entry denied.',
        ]);
    }

    /**
     * The agenda page passes __(ucfirst($item->type->value)) — a lookup key built from the
     * enum's value at runtime, so it never appears as a literal string anywhere to be found
     * by a normal grep. Every case is translated here explicitly instead.
     */
    public function test_every_agenda_item_type_has_an_arabic_translation(): void
    {
        $this->assertAllTranslated(array_map(
            fn (AgendaItemType $type) => ucfirst($type->value),
            AgendaItemType::cases(),
        ));
    }

    /**
     * The Landing Page Content screen passes __(ucwords(str_replace('-', ' ', $section)))
     * for the section-visibility checkboxes — again a runtime-built key, not a literal one.
     */
    public function test_every_toggleable_landing_section_has_an_arabic_translation(): void
    {
        $this->assertAllTranslated(array_map(
            fn (string $section) => ucwords(str_replace('-', ' ', $section)),
            Event::TOGGLEABLE_SECTIONS,
        ));
    }
}
