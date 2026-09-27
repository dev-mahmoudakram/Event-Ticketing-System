<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AgendaItem;
use App\Models\Event;
use App\Models\Location;
use App\Models\Speaker;
use App\Models\Ticket;
use App\Models\Workshop;
use App\Models\WorkshopBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_agenda_shows_a_career_style_card_for_each_session(): void
    {
        $event = Event::factory()->create();
        $stage = Location::factory()->for($event)->create(['name_en' => 'Main Stage']);
        $withPhoto = Speaker::factory()->for($event)->create(['name_en' => 'Maya Chen', 'photo_path' => 'speakers/maya.jpg']);
        $noPhoto = Speaker::factory()->for($event)->create(['name_en' => 'Omar Ali', 'photo_path' => null]);
        $item = AgendaItem::factory()->for($event)->ofType('Panel')->create([
            'title_en' => 'Worth the Hype?', 'day_date' => '2026-08-15', 'start_time' => '11:15', 'end_time' => '12:00',
            'location_id' => $stage->id, 'description_en' => '<p>Are certificates a must?</p>',
        ]);
        $item->syncSpeakersInOrder([$withPhoto->id, $noPhoto->id]);

        $this->get(route('agenda.show', $event).'?lang=en')
            ->assertOk()
            ->assertSee('id="session-'.$item->id.'"', false)
            ->assertSee('11:15 – 12:00')
            ->assertSee('Panel')
            ->assertSee('Main Stage')
            ->assertSee('Maya Chen')
            ->assertSee('speakers/maya.jpg', false)
            ->assertSee('>O<', false)
            ->assertSee('Are certificates a must?');
    }

    public function test_more_than_four_speakers_collapse_into_a_count(): void
    {
        $event = Event::factory()->create();
        $item = AgendaItem::factory()->for($event)->create();
        $item->syncSpeakersInOrder(Speaker::factory()->for($event)->count(6)->create()->pluck('id')->all());

        $this->get(route('agenda.show', $event).'?lang=en')->assertSee('+2 more');
    }

    public function test_workshops_appear_with_a_booking_button_and_sessions_without(): void
    {
        $event = Event::factory()->create();
        AgendaItem::factory()->for($event)->create(['day_date' => '2026-08-15']);
        $workshop = Workshop::factory()->for($event)->create(['day_date' => '2026-08-15', 'capacity' => 20]);
        // Capacity 0 means unlimited, so "full" is a one-seat workshop whose seat is taken.
        $full = Workshop::factory()->for($event)->create(['day_date' => '2026-08-15', 'capacity' => 1]);
        WorkshopBooking::create(['ticket_id' => Ticket::factory()->for($event)->create()->id, 'workshop_id' => $full->id]);

        $page = $this->get(route('agenda.show', $event).'?lang=en')->assertOk();

        $page->assertSee('id="workshop-'.$workshop->id.'"', false)
            ->assertSee('href="'.route('workshops.book', $event).'"', false)
            ->assertDontSee(route('workshops.book', $event).'?', false)
            ->assertSee('Book your seat');
        $this->assertSame(1, substr_count($page->getContent(), 'data-book-seat'));
        $this->assertStringContainsString('20 seats left', $page->getContent());
    }

    public function test_break_sessions_render_as_a_slim_line(): void
    {
        $event = Event::factory()->create();
        $break = AgendaItem::factory()->for($event)->ofType('Break')->create(['title_en' => 'Coffee']);

        $this->get(route('agenda.show', $event).'?lang=en')
            ->assertSee('data-break-entry="session-'.$break->id.'"', false);
    }

    public function test_every_card_has_a_matching_detail_template(): void
    {
        $event = Event::factory()->create();
        AgendaItem::factory()->for($event)->count(2)->create();
        Workshop::factory()->for($event)->create();

        $html = $this->get(route('agenda.show', $event))->getContent();
        preg_match_all('/data-schedule-open="([a-z]+-\d+)"/', $html, $cards);

        $this->assertCount(3, $cards[1]);
        foreach ($cards[1] as $anchor) {
            $this->assertStringContainsString('id="detail-'.$anchor.'"', $html);
        }
    }

    public function test_day_tabs_and_type_filters_appear_for_a_multi_day_event(): void
    {
        $event = Event::factory()->create();
        AgendaItem::factory()->for($event)->ofType('Keynote')->create(['day_date' => '2026-08-15']);
        AgendaItem::factory()->for($event)->ofType('Panel')->create(['day_date' => '2026-08-16']);

        $this->get(route('agenda.show', $event).'?lang=en')
            ->assertSee('data-day-tab="0"', false)
            ->assertSee('data-day-tab="1"', false)
            ->assertSee('data-type-filter="all"', false)
            ->assertSee('Keynote');
    }

    public function test_agenda_page_shows_empty_state_when_no_items(): void
    {
        $event = Event::factory()->create();

        $this->get(route('agenda.show', $event).'?lang=en')->assertOk()->assertSee('No agenda items yet.');
    }

    public function test_agenda_page_links_back_to_landing_page(): void
    {
        $event = Event::factory()->create();

        $this->get(route('agenda.show', $event))->assertSee(route('landing.show', $event), false);
    }

    public function test_a_workshop_pop_up_links_to_the_workshop_page(): void
    {
        $event = Event::factory()->create();
        $workshop = Workshop::factory()->for($event)->create();

        $this->get(route('agenda.show', $event).'?lang=en')
            ->assertSee('href="'.route('workshops.show', [$event, $workshop]).'"', false)
            ->assertSee('Workshop details');
    }

    public function test_cards_open_their_details_from_a_real_button_not_a_button_role(): void
    {
        $event = Event::factory()->create();
        $item = AgendaItem::factory()->for($event)->create();
        Workshop::factory()->for($event)->create();

        $html = $this->get(route('agenda.show', $event))->getContent();

        $this->assertStringNotContainsString('<article id="session-'.$item->id.'" data-schedule-open="session-'.$item->id.'" role="button"', $html);
        $this->assertSame(2, substr_count($html, 'data-schedule-trigger'));
    }

    public function test_a_session_typed_workshop_and_a_real_workshop_share_one_filter_chip(): void
    {
        $event = Event::factory()->create();
        AgendaItem::factory()->for($event)->ofType('Workshop')->create(['day_date' => '2026-08-15']);
        Workshop::factory()->for($event)->create(['day_date' => '2026-08-15']);

        $html = $this->get(route('agenda.show', $event).'?lang=en')->getContent();

        preg_match_all('/data-type-filter="([^"]+)"/', $html, $chips);
        $this->assertCount(2, $chips[1], 'Expected "All" and one "Workshop" chip.');
    }
}
