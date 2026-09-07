<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\LandingPageSection;
use App\Models\Event;
use App\Models\Faq;
use App\Models\GalleryPhoto;
use App\Models\LandingPageContent;
use App\Models\Speaker;
use App\Models\Testimonial;
use App\Models\TicketType;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders_for_an_event(): void
    {
        $event = Event::factory()->create([
            'slug' => 'ccs-2026',
            'name_en' => 'Content Creators Summit',
            'status' => EventStatus::Published,
        ]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertStatus(200);
        $response->assertSee('Content Creators Summit');
    }

    public function test_landing_page_shows_about_content_when_present(): void
    {
        $event = Event::factory()->create();
        LandingPageContent::factory()->for($event)->create([
            'section' => LandingPageSection::About,
            'field_key' => 'body',
            'value_en' => 'Where digital creators meet.',
        ]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('Where digital creators meet.');
    }

    public function test_about_section_omitted_when_no_content(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('landing.show', $event));

        $response->assertDontSee('id="about"', false);
    }

    public function test_speakers_section_lists_speaker_names(): void
    {
        $event = Event::factory()->create();
        Speaker::factory()->for($event)->create(['name_en' => 'Jane Creator']);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('Jane Creator');
    }

    public function test_partners_section_omitted_when_no_sponsors(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('landing.show', $event));

        $response->assertDontSee('id="partners"', false);
    }

    public function test_faq_section_lists_questions(): void
    {
        $event = Event::factory()->create();
        Faq::factory()->for($event)->create(['question_en' => 'How do I pay?']);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('How do I pay?');
    }

    public function test_location_section_shows_venue_intro(): void
    {
        $event = Event::factory()->create();
        LandingPageContent::factory()->for($event)->create([
            'section' => LandingPageSection::Location,
            'field_key' => 'intro',
            'value_en' => 'Held at the Convention Center.',
        ]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('Held at the Convention Center.');
    }

    public function test_workshops_teaser_links_to_workshops_index(): void
    {
        $event = Event::factory()->create();
        Workshop::factory()->for($event)->create();

        $response = $this->get(route('landing.show', $event));

        $response->assertSee(route('workshops.index', $event), false);
    }

    public function test_workshops_section_shows_description_and_capacity(): void
    {
        $event = Event::factory()->create();
        Workshop::factory()->for($event)->create([
            'name_en' => 'Editing at Scale', 'description_en' => 'Hands-on editing techniques.', 'capacity' => 40,
        ]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('Editing at Scale');
        $response->assertSee('Hands-on editing techniques.');
        $response->assertSee('40 seats');
    }

    public function test_nav_links_to_agenda_page(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('landing.show', $event));

        $response->assertSee(route('agenda.show', $event), false);
    }

    public function test_tickets_section_button_opens_request_modal_for_that_ticket_type(): void
    {
        $event = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create(['name_en' => 'General']);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('General');
        $response->assertSee('$store.ticketRequest.show(\''.$ticketType->id.'\')', false);
    }

    public function test_awards_teaser_links_to_awards_page(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('landing.show', $event));

        $response->assertSee(route('awards.show', $event), false);
    }

    public function test_hero_headline_overrides_event_name_when_set(): void
    {
        $event = Event::factory()->create(['name_en' => 'Content Creators Summit']);
        LandingPageContent::factory()->for($event)->create([
            'section' => LandingPageSection::Hero,
            'field_key' => 'headline',
            'value_en' => 'The Future of Content',
        ]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        // The hero sets the headline as a stacked wordmark, so its words are split across
        // lines rather than appearing as one contiguous string.
        $response->assertSee('The Future of');
        $response->assertSee('Content');
    }

    public function test_hero_eyebrow_shows_locale_correct_venue(): void
    {
        $event = Event::factory()->create([
            'venue_name_ar' => 'قاعة المؤتمرات', 'venue_name_en' => 'Convention Hall',
        ]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('Convention Hall');
        $response->assertDontSee('قاعة المؤتمرات');
    }

    public function test_tickets_section_shows_workshop_slot_label(): void
    {
        $event = Event::factory()->create();
        TicketType::factory()->for($event)->create(['name_en' => 'VIP', 'workshop_slot_count' => 1]);
        TicketType::factory()->for($event)->create(['name_en' => 'General', 'workshop_slot_count' => 0]);
        TicketType::factory()->for($event)->create(['name_en' => 'Platinum', 'workshop_slot_count' => null]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('1 workshop included');
        $response->assertSee('No workshops included');
        $response->assertSee('Unlimited workshops');
    }

    public function test_tickets_section_lists_features(): void
    {
        $event = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create(['name_en' => 'General']);
        $ticketType->features()->create(['text_ar' => 'دخول كامل', 'text_en' => 'Full event access', 'sort_order' => 0]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('Full event access');
    }

    public function test_gallery_section_lists_photos(): void
    {
        $event = Event::factory()->create();
        GalleryPhoto::factory()->for($event)->create(['caption_en' => 'Opening night crowd']);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('id="gallery"', false);
        $response->assertSee('Opening night crowd');
    }

    public function test_gallery_section_omitted_when_no_photos(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('landing.show', $event));

        $response->assertDontSee('id="gallery"', false);
    }

    public function test_testimonials_section_lists_quotes(): void
    {
        $event = Event::factory()->create();
        Testimonial::factory()->for($event)->create(['quote_en' => 'The best conference all year.']);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('id="testimonials"', false);
        $response->assertSee('The best conference all year.');
    }

    public function test_testimonials_section_omitted_when_none_exist(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('landing.show', $event));

        $response->assertDontSee('id="testimonials"', false);
    }

    public function test_a_one_day_event_shows_a_single_date_not_a_range(): void
    {
        $event = Event::factory()->create([
            'status' => EventStatus::Published,
            'start_date' => '2026-12-26',
            'end_date' => '2026-12-26',
        ]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('Dec 26, 2026');
        $response->assertDontSee('Dec 26–26', false);
        $response->assertDontSee('Dec 26&ndash;26', false);
    }

    public function test_a_multi_day_event_still_shows_a_range(): void
    {
        $event = Event::factory()->create([
            'status' => EventStatus::Published,
            'start_date' => '2026-12-26',
            'end_date' => '2026-12-28',
        ]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('Dec 26', false);
        $response->assertSee('28, 2026', false);
    }
}
