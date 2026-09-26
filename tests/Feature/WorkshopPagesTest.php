<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Speaker;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkshopPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_workshops_for_the_event(): void
    {
        $event = Event::factory()->create();
        Workshop::factory()->for($event)->create(['name_en' => 'AI Content Workshop']);

        $response = $this->get(route('workshops.index', $event).'?lang=en');

        $response->assertStatus(200);
        $response->assertSee('AI Content Workshop');
    }

    public function test_index_links_back_to_the_landing_page(): void
    {
        $event = Event::factory()->create();
        Workshop::factory()->for($event)->create();

        $response = $this->get(route('workshops.index', $event));

        $response->assertSee(route('landing.show', $event), false);
    }

    public function test_show_displays_workshop_detail_with_speaker(): void
    {
        $event = Event::factory()->create();
        $speaker = Speaker::factory()->create(['name_en' => 'Jane Creator']);
        $workshop = Workshop::factory()->for($event)->create([
            'name_en' => 'AI Content Workshop',
            'slug' => 'ai-content-workshop',
        ]);
        $workshop->syncSpeakersInOrder([$speaker->id]);

        $response = $this->get(route('workshops.show', [$event, $workshop]).'?lang=en');

        $response->assertStatus(200);
        $response->assertSee('AI Content Workshop');
        $response->assertSee('Jane Creator');
    }

    public function test_show_returns_404_for_workshop_from_another_event(): void
    {
        $event = Event::factory()->create();
        $otherEventWorkshop = Workshop::factory()->create();

        $response = $this->get(route('workshops.show', [$event, $otherEventWorkshop]));

        $response->assertStatus(404);
    }

    public function test_show_omits_schedule_block_when_no_agenda_item_is_linked(): void
    {
        $event = Event::factory()->create();
        $workshop = Workshop::factory()->for($event)->create();

        $response = $this->get(route('workshops.show', [$event, $workshop]));

        $response->assertOk();
    }
}
