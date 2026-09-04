<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatorsHubHomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_with_key_sections(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('id="hero"', false);
        $response->assertSee('id="about"', false);
        $response->assertSee('id="events"', false);
        $response->assertSee('id="community"', false);
        $response->assertSee('id="partners"', false);
        $response->assertSee('id="contact"', false);
    }

    public function test_home_page_navigation_links_to_the_events_index(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('href="'.route('events.index').'"', false);
    }

    public function test_events_index_shows_a_coming_soon_state_when_no_events_exist(): void
    {
        $response = $this->get(route('events.index'));

        $response->assertOk();
        $response->assertSee(__('Coming Soon'));
    }

    public function test_home_page_shows_the_coming_soon_state_when_no_events_exist(): void
    {
        $response = $this->get(route('home').'?lang=en');

        $response->assertSee(__('Our first gathering is in the works.'));
    }

    public function test_home_page_shows_a_real_published_event_as_featured(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published, 'name_en' => 'Design Week Cairo']);

        $response = $this->get(route('home').'?lang=en');

        $response->assertSee('Design Week Cairo');
        $response->assertSee('href="'.route('landing.show', $event).'"', false);
        $response->assertDontSee(__('Our first gathering is in the works.'));
    }

    public function test_home_page_does_not_show_draft_events(): void
    {
        Event::factory()->create(['status' => EventStatus::Draft, 'name_en' => 'Secret Draft Event']);

        $response = $this->get(route('home').'?lang=en');

        $response->assertDontSee('Secret Draft Event');
        $response->assertSee(__('Our first gathering is in the works.'));
    }

    public function test_events_index_lists_real_published_events(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published, 'name_en' => 'Build Expo']);

        $response = $this->get(route('events.index').'?lang=en');

        $response->assertSee('Build Expo');
        $response->assertSee('href="'.route('landing.show', $event).'"', false);
    }
}
