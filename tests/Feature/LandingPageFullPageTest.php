<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\LandingPageSection;
use App\Models\AgendaItem;
use App\Models\Event;
use App\Models\Faq;
use App\Models\GalleryPhoto;
use App\Models\LandingPageContent;
use App\Models\Speaker;
use App\Models\Sponsor;
use App\Models\Testimonial;
use App\Models\TicketType;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageFullPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_fully_populated_event_renders_every_section_in_order(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        Speaker::factory()->for($event)->create();
        Workshop::factory()->for($event)->create();
        AgendaItem::factory()->for($event)->create();
        TicketType::factory()->for($event)->create();
        Sponsor::factory()->for($event)->create();
        Faq::factory()->for($event)->create();
        Testimonial::factory()->for($event)->create();
        GalleryPhoto::factory()->for($event)->create();
        LandingPageContent::factory()->for($event)->create(['section' => LandingPageSection::About, 'field_key' => 'body']);
        LandingPageContent::factory()->for($event)->create(['section' => LandingPageSection::Location, 'field_key' => 'intro']);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertOk();
        $response->assertSeeInOrder([
            'id="hero"', 'id="about"', 'id="speakers"', 'id="workshops"',
            'id="tickets"', 'id="awards"', 'id="gallery"',
            'id="testimonials"', 'id="partners"', 'id="faq"', 'id="location"',
            'id="contact"', 'id="newsletter"',
        ], false);
    }

    public function test_minimal_event_still_renders_ok(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);

        $response = $this->get(route('landing.show', $event));

        $response->assertOk();
        $response->assertSee('id="hero"', false);
        $response->assertSee('id="awards"', false);
        $response->assertSee('id="contact"', false);
        $response->assertSee('id="newsletter"', false);
    }

    public function test_ticket_card_shows_its_description(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        TicketType::factory()->for($event)->create([
            'name_en' => 'Standard',
            'description_en' => 'Everything you need for the main stage.',
            'price' => 500,
        ]);
        TicketType::factory()->for($event)->create([
            'name_en' => 'VIP',
            'description_en' => 'Front row access and the after-party.',
            'price' => 2000,
        ]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertOk();
        $response->assertSee('Everything you need for the main stage.');
        $response->assertSee('Front row access and the after-party.');
    }

    public function test_only_the_ticket_type_marked_popular_gets_the_standout_treatment(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        // The cheaper tier is the one marked popular, proving the highlight follows the
        // admin's choice rather than defaulting back to whichever tier costs the most.
        TicketType::factory()->for($event)->create(['name_en' => 'Standard', 'price' => 2000, 'is_popular' => false]);
        TicketType::factory()->for($event)->create(['name_en' => 'VIP', 'price' => 500, 'is_popular' => true]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertOk();
        $response->assertSeeInOrder(['border-ccs-coral', 'VIP'], false);
        $this->assertSame(1, substr_count($response->getContent(), 'border-ccs-coral'));
        $response->assertSee('Most Popular');
    }

    public function test_the_popular_badge_shows_the_admins_custom_wording(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        TicketType::factory()->for($event)->create([
            'is_popular' => true,
            'popular_label_en' => 'Best Value',
        ]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertOk();
        $response->assertSee('Best Value');
        $response->assertDontSee('Most Popular');
    }

    public function test_no_ticket_type_is_highlighted_when_none_is_marked_popular(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        TicketType::factory()->for($event)->create(['is_popular' => false]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertOk();
        $response->assertDontSee('Most Popular');
        $this->assertSame(0, substr_count($response->getContent(), 'border-ccs-coral'));
    }

    public function test_ticket_card_shows_the_struck_through_original_price_on_sale(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        TicketType::factory()->for($event)->create([
            'name_en' => 'General', 'price' => 300, 'original_price' => 450, 'currency' => 'EGP',
        ]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertOk();
        $response->assertSeeInOrder(['line-through', '450', '300'], false);
    }

    public function test_ticket_card_does_not_show_a_struck_through_price_when_not_on_sale(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        TicketType::factory()->for($event)->create(['price' => 300, 'original_price' => null]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertOk();
        $response->assertDontSee('line-through', false);
    }
}
