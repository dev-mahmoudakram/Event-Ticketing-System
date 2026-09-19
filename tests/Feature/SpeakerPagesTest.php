<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Speaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpeakerPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_every_speaker_for_the_event(): void
    {
        $event = Event::factory()->create();
        Speaker::factory()->for($event)->create(['name_en' => 'Featured Speaker', 'is_featured' => true]);
        Speaker::factory()->for($event)->create(['name_en' => 'Non-featured Speaker', 'is_featured' => false]);

        $response = $this->get(route('speakers.index', $event).'?lang=en');

        $response->assertStatus(200);
        $response->assertSee('Featured Speaker');
        $response->assertSee('Non-featured Speaker');
    }

    public function test_index_links_back_to_the_landing_page(): void
    {
        $event = Event::factory()->create();
        Speaker::factory()->for($event)->create();

        $response = $this->get(route('speakers.index', $event));

        $response->assertSee(route('landing.show', $event), false);
    }

    public function test_index_shows_a_message_when_there_are_no_speakers(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('speakers.index', $event).'?lang=en');

        $response->assertStatus(200);
        $response->assertSee('No speakers yet.');
    }

    public function test_a_speakers_page_from_another_event_is_not_shown(): void
    {
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        Speaker::factory()->for($otherEvent)->create(['name_en' => 'Other Event Speaker']);

        $response = $this->get(route('speakers.index', $event).'?lang=en');

        $response->assertDontSee('Other Event Speaker');
    }
}
