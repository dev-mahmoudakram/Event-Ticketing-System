<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Carry the audiences section out of the key-value store and into its own tables.
     *
     * The section used to live as twenty-four fixed fields: two sides, four cards each, every
     * slot named in the content registry. Whatever an admin typed into those fields is real
     * copy, so it is moved rather than dropped and re-seeded — a re-seed would silently
     * replace their wording with the defaults.
     */
    public function up(): void
    {
        $stored = DB::table('site_contents')
            ->where('section', 'audiences')
            ->get()
            ->keyBy('field_key');

        // Nothing to move on an install that has never had content — a brand new database,
        // or a test one. Seeding the old defaults there would invent two tabs nobody asked
        // for, so the section simply starts empty and the admin creates its tabs.
        if (DB::table('site_contents')->count() === 0) {
            return;
        }

        // A section an admin never edited has no rows at all: the page was rendering the
        // registry's defaults. Those defaults are the real current copy, so they seed the
        // new tables rather than leaving them blank.
        $defaults = [
            'builders_tab' => 'If you design or build',
            'builders_lede' => 'For interior designers, architects, contractors and developers.',
            'builders_cta' => 'Get in touch',
            'builders_one_title' => 'Meet your next collaborator',
            'builders_one_body' => 'Sit with the studios, contractors and suppliers you would otherwise only ever email.',
            'builders_two_title' => 'Handle the materials',
            'builders_two_body' => 'See finishes and products in person, before they reach a supplier catalogue.',
            'builders_three_title' => 'Learn from finished work',
            'builders_three_body' => 'Sessions run by people describing projects they actually completed, including what went wrong.',
            'builders_four_title' => 'Show what you have built',
            'builders_four_body' => 'Put your projects in front of the people commissioning the next ones.',
            'brands_tab' => 'If you supply or sponsor',
            'brands_lede' => 'For manufacturers, material suppliers and brands serving the sector.',
            'brands_cta' => 'Get in touch',
            'brands_one_title' => 'Reach the specifiers',
            'brands_one_body' => 'The architects and contractors who decide what actually goes into a build.',
            'brands_two_title' => 'Demonstrate, do not advertise',
            'brands_two_body' => 'Let people handle the product instead of reading about it.',
            'brands_three_title' => 'Join the programme',
            'brands_three_body' => 'Take part in the sessions, not just the floor space around them.',
            'brands_four_title' => 'Back an event',
            'brands_four_body' => 'Partner with us and help shape how the industry gathers.',
        ];

        $value = function (string $key, string $locale) use ($stored, $defaults): ?string {
            $saved = $stored->get($key)?->{'value_'.$locale};

            if (trim((string) $saved) !== '') {
                return $saved;
            }

            // Arabic has no default in the registry; an empty Arabic column is correct and
            // the site falls back to English until somebody translates it.
            return $locale === 'en' ? ($defaults[$key] ?? null) : null;
        };

        foreach ([['builders', 0], ['brands', 1]] as [$side, $order]) {
            $tabId = DB::table('audience_tabs')->insertGetId([
                'label_ar' => $value($side.'_tab', 'ar'),
                'label_en' => $value($side.'_tab', 'en'),
                'lede_ar' => $value($side.'_lede', 'ar'),
                'lede_en' => $value($side.'_lede', 'en'),
                'cta_ar' => $value($side.'_cta', 'ar'),
                'cta_en' => $value($side.'_cta', 'en'),
                'sort_order' => $order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (['one', 'two', 'three', 'four'] as $index => $slot) {
                $prefix = $side.'_'.$slot.'_';

                DB::table('audience_cards')->insert([
                    'audience_tab_id' => $tabId,
                    'title_ar' => $value($prefix.'title', 'ar'),
                    'title_en' => $value($prefix.'title', 'en'),
                    'body_ar' => $value($prefix.'body', 'ar'),
                    'body_en' => $value($prefix.'body', 'en'),
                    // Images are stored once for both locales, in value_en.
                    'image_path' => $value($prefix.'image', 'en'),
                    'sort_order' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Only the moved fields go. The section heading and body still belong to the
        // key-value store, and deleting the whole section would take them with it.
        $moved = ['tab', 'lede', 'cta'];
        foreach (['one', 'two', 'three', 'four'] as $slot) {
            foreach (['title', 'body', 'image'] as $part) {
                $moved[] = $slot.'_'.$part;
            }
        }

        $keys = [];
        foreach (['builders', 'brands'] as $side) {
            foreach ($moved as $suffix) {
                $keys[] = $side.'_'.$suffix;
            }
        }

        DB::table('site_contents')
            ->where('section', 'audiences')
            ->whereIn('field_key', $keys)
            ->delete();
    }

    public function down(): void
    {
        DB::table('audience_cards')->delete();
        DB::table('audience_tabs')->delete();
    }
};
