<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InvitationRequestStatus;
use App\Enums\Permission;
use App\Enums\SpeakerRequestStatus;
use App\Enums\SponsorRequestStatus;
use App\Enums\TicketRequestFieldType;
use App\Enums\TicketStatus;
use App\Models\Event;
use App\Support\SiteContentRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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

    /**
     * Sweeps every literal __('...') key in the views and app code — public pages, admin,
     * check-in and emails alike. Presence is what's checked: a few strings (sample email
     * addresses, the brand name) are rightly the same in both languages.
     */
    public function test_every_literal_translation_key_exists_in_arabic(): void
    {
        $arabic = $this->arabicStrings();
        $missing = [];

        $files = [...File::allFiles(resource_path('views')), ...File::allFiles(app_path())];

        foreach ($files as $file) {
            preg_match_all('/(?:__|@lang|trans)\(\s*(\'(?:\\\\.|[^\'\\\\])*\'|"(?:\\\\.|[^"\\\\])*")/', $file->getContents(), $matches);

            foreach ($matches[1] as $literal) {
                $key = stripcslashes(substr($literal, 1, -1));

                if (preg_match('/^[a-z_]+\.[a-z0-9_.]+$/', $key) === 1) {
                    continue;
                }

                if (! array_key_exists($key, $arabic)) {
                    $missing[$key] = $file->getRelativePathname();
                }
            }
        }

        $this->assertSame([], $missing, 'These strings have no Arabic translation: '.json_encode($missing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
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
     * Status and type labels come from each enum's label() method rather than a literal in a
     * view, so they are checked here: every case must read differently in Arabic.
     */
    public function test_every_enum_label_has_an_arabic_translation(): void
    {
        $untranslated = [];

        foreach ([TicketStatus::class, SpeakerRequestStatus::class, SponsorRequestStatus::class, InvitationRequestStatus::class, Permission::class, TicketRequestFieldType::class] as $enum) {
            foreach ($enum::cases() as $case) {
                app()->setLocale('en');
                $english = $case->label();
                app()->setLocale('ar');

                if ($case->label() === $english) {
                    $untranslated[] = class_basename($enum).'::'.$case->name;
                }
            }
        }

        $this->assertSame([], $untranslated);
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

    /**
     * Arabic has six plural forms; a two-form translation turns "5 seats left" into "one seat
     * left". Counts from 2 up must show the number.
     */
    public function test_arabic_counts_show_the_number(): void
    {
        app()->setLocale('ar');

        $this->assertStringNotContainsString('واحد', trans_choice(':count seat left|:count seats left', 2, ['count' => 2]));

        foreach ([3, 5, 12, 100] as $count) {
            $this->assertStringContainsString((string) $count, trans_choice(':count seat left|:count seats left', $count, ['count' => $count]));
            $this->assertStringContainsString((string) $count, trans_choice('In use by :count session or workshop — move it first.|In use by :count sessions or workshops — move them first.', $count, ['count' => $count]));
        }
    }
}
