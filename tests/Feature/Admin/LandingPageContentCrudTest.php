<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\LandingPageSection;
use App\Models\Event;
use App\Models\LandingPageContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageContentCrudTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return [
            'hero_headline_ar' => 'العنوان', 'hero_headline_en' => 'Headline',
            'about_body_ar' => 'نبذة', 'about_body_en' => 'About body',
            'location_intro_ar' => 'الموقع', 'location_intro_en' => 'Location intro',
            'awards_teaser_blurb_ar' => 'الجوائز', 'awards_teaser_blurb_en' => 'Awards blurb',
            'stats_attendees_count_ar' => '٢٠٠+', 'stats_attendees_count_en' => '200+',
            'stats_countries_count_ar' => '١٥', 'stats_countries_count_en' => '15',
        ];
    }

    public function test_admin_can_set_landing_page_content(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.events.content.update', $event), $this->payload());

        $response->assertRedirect(route('admin.events.content.edit', $event));
        $this->assertSame(6, $event->landingPageContent()->count());
        $this->assertDatabaseHas('landing_page_content', [
            'event_id' => $event->id,
            'section' => LandingPageSection::Hero->value,
            'field_key' => 'headline',
            'value_en' => 'Headline',
        ]);
    }

    public function test_resubmitting_content_updates_instead_of_duplicating(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $this->actingAs($admin)->put(route('admin.events.content.update', $event), $this->payload());

        $updated = array_merge($this->payload(), ['hero_headline_en' => 'Updated Headline']);
        $this->actingAs($admin)->put(route('admin.events.content.update', $event), $updated);

        $this->assertSame(6, $event->landingPageContent()->count());
        $this->assertDatabaseHas('landing_page_content', [
            'event_id' => $event->id, 'field_key' => 'headline', 'value_en' => 'Updated Headline',
        ]);
    }

    public function test_edit_form_prefills_existing_values(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        LandingPageContent::factory()->for($event)->create([
            'section' => LandingPageSection::Hero, 'field_key' => 'headline', 'value_en' => 'Existing Headline',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.events.content.edit', $event));

        $response->assertSee('Existing Headline');
    }

    /**
     * The rich-text fields (About Body, Location Intro, Awards Blurb) are rendered on the
     * public page with {!! !!} rather than {{ }}, since they may legitimately contain their
     * own HTML tags. That is only safe because this controller sanitizes them before they are
     * ever stored — this proves the real HTTP write path does that, not just the isolated
     * RichText helper in tests/Unit/RichTextTest.php.
     */
    public function test_rich_text_fields_are_sanitized_on_save(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $payload = $this->payload();
        $payload['about_body_en'] = '<p>Safe</p><script>alert(1)</script>';
        $payload['location_intro_en'] = '<p onclick="alert(1)">Safe</p>';
        $payload['awards_teaser_blurb_en'] = '<a href="javascript:alert(1)">click</a>';

        $this->actingAs($admin)->put(route('admin.events.content.update', $event), $payload);

        $this->assertDatabaseHas('landing_page_content', [
            'event_id' => $event->id, 'field_key' => 'body', 'value_en' => '<p>Safe</p>',
        ]);
        $this->assertDatabaseHas('landing_page_content', [
            'event_id' => $event->id, 'field_key' => 'intro', 'value_en' => '<p>Safe</p>',
        ]);
        $this->assertDatabaseHas('landing_page_content', [
            'event_id' => $event->id, 'field_key' => 'blurb', 'value_en' => '<p><a>click</a></p>',
        ]);
    }

    /**
     * A plain field (the hero headline) is not run through the HTML purifier at all — its
     * value should reach the database byte-for-byte, proving the richtext flag in
     * LandingPageContentController::FIELDS actually gates which fields are sanitized.
     */
    public function test_non_richtext_fields_are_stored_exactly_as_submitted(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $payload = $this->payload();
        $payload['hero_headline_en'] = 'Plain & unmodified <not-a-real-tag>';

        $this->actingAs($admin)->put(route('admin.events.content.update', $event), $payload);

        $this->assertDatabaseHas('landing_page_content', [
            'event_id' => $event->id, 'field_key' => 'headline', 'value_en' => 'Plain & unmodified <not-a-real-tag>',
        ]);
    }
}
