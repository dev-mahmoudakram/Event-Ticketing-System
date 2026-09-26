<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AgendaItem;
use App\Models\Event;
use App\Models\Speaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_agenda_page_lists_items_grouped_by_day(): void
    {
        $event = Event::factory()->create();
        AgendaItem::factory()->for($event)->create([
            'title_en' => 'Opening Keynote',
            'day_date' => '2026-08-15',
        ]);

        $response = $this->get(route('agenda.show', $event).'?lang=en');

        $response->assertStatus(200);
        $response->assertSee('Opening Keynote');
    }

    public function test_agenda_page_shows_real_session_details(): void
    {
        $event = Event::factory()->create();
        $speaker = Speaker::factory()->for($event)->create(['name_en' => 'Maya Chen']);
        AgendaItem::factory()->for($event)->create([
            'title_en' => 'Opening Keynote', 'start_time' => '09:00',
        ])->syncSpeakersInOrder([$speaker->id]);

        $response = $this->get(route('agenda.show', $event).'?lang=en');

        $response->assertSee('Opening Keynote');
        $response->assertSee('Maya Chen');
        $response->assertSee('09:00');
    }

    public function test_agenda_page_shows_empty_state_when_no_items(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('agenda.show', $event).'?lang=en');

        $response->assertStatus(200);
        $response->assertSee('No agenda items yet.');
    }

    public function test_agenda_page_links_back_to_landing_page(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('agenda.show', $event));

        $response->assertSee(route('landing.show', $event), false);
    }
}
