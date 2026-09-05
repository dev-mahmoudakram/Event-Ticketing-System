<?php

declare(strict_types=1);

namespace Tests\Feature;

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
}
