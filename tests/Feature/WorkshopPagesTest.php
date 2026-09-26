<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Location;
use App\Models\Speaker;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkshopPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_workshops_as_schedule_cards(): void
    {
        $event = Event::factory()->create();
        $workshop = Workshop::factory()->for($event)->create(['name_en' => 'AI Content Workshop']);

        $this->get(route('workshops.index', $event).'?lang=en')
            ->assertOk()
            ->assertSee('AI Content Workshop')
            ->assertSee('id="workshop-'.$workshop->id.'"', false)
            ->assertSee('id="detail-workshop-'.$workshop->id.'"', false);
    }

    public function test_an_unscheduled_workshop_still_shows_on_the_workshops_page(): void
    {
        $event = Event::factory()->create();
        Workshop::factory()->for($event)->unscheduled()->create(['name_en' => 'Time TBC']);

        $this->get(route('workshops.index', $event).'?lang=en')->assertOk()->assertSee('Time TBC')->assertSee('Time to be announced');
    }

    public function test_index_links_back_to_the_landing_page(): void
    {
        $event = Event::factory()->create();
        Workshop::factory()->for($event)->create();

        $this->get(route('workshops.index', $event))->assertSee(route('landing.show', $event), false);
    }

    public function test_the_landing_teaser_uses_the_schedule_card(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $workshop = Workshop::factory()->for($event)->create();

        $this->get(route('landing.show', $event))->assertSee('id="workshop-'.$workshop->id.'"', false);
    }

    public function test_show_displays_the_schedule_location_and_every_speaker(): void
    {
        $event = Event::factory()->create();
        $location = Location::factory()->for($event)->create(['name_en' => 'Room A']);
        $speakers = Speaker::factory()->for($event)->count(2)->sequence(['name_en' => 'Jane Creator'], ['name_en' => 'Omar Ali'])->create();
        $workshop = Workshop::factory()->for($event)->create([
            'name_en' => 'AI Content Workshop', 'day_date' => '2026-08-15', 'start_time' => '14:00', 'end_time' => '15:30', 'location_id' => $location->id,
        ]);
        $workshop->syncSpeakersInOrder($speakers->pluck('id')->all());

        $this->get(route('workshops.show', [$event, $workshop]).'?lang=en')
            ->assertOk()
            ->assertSee('14:00')->assertSee('15:30')
            ->assertSee('Room A')
            ->assertSee('Jane Creator')->assertSee('Omar Ali');
    }

    public function test_show_returns_404_for_workshop_from_another_event(): void
    {
        $event = Event::factory()->create();

        $this->get(route('workshops.show', [$event, Workshop::factory()->create()]))->assertNotFound();
    }
}
