<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_menu_lists_every_event(): void
    {
        Event::factory()->create(['name_en' => 'Content Creators Summit']);
        Event::factory()->create(['name_en' => 'Cairo Design Week']);

        $response = $this->actingAs(User::factory()->create())->get(route('admin.dashboard').'?lang=en');

        $response->assertSee('Content Creators Summit');
        $response->assertSee('Cairo Design Week');
        $response->assertSee('All events');
    }

    public function test_each_event_carries_its_own_sections(): void
    {
        $event = Event::factory()->create(['name_en' => 'Content Creators Summit']);

        $response = $this->actingAs(User::factory()->create())->get(route('admin.dashboard').'?lang=en');

        $response->assertSee(route('admin.events.speakers.index', $event), false);
        $response->assertSee(route('admin.events.ticket-types.index', $event), false);
        $response->assertSee(route('admin.events.content.edit', $event), false);
    }

    public function test_the_menu_opens_on_the_event_being_worked_on(): void
    {
        $event = Event::factory()->create();
        Event::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.events.speakers.index', $event).'?lang=en');

        // The branch for this event starts open; the other one starts closed.
        $response->assertSee('open: true, event: '.$event->id, false);
    }

    public function test_the_platform_pages_are_grouped_apart_from_the_events(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('admin.dashboard').'?lang=en');

        $response->assertSee(route('admin.site-content.index'), false);
        $response->assertSee(route('admin.hero-slides.index'), false);
        $response->assertSee(route('admin.hub-partners.index'), false);
        $response->assertSee(route('admin.site-faqs.index'), false);
    }

    public function test_an_empty_platform_says_so_rather_than_showing_an_empty_branch(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard').'?lang=en')
            ->assertSee('No events yet.');
    }

    public function test_the_admin_wears_the_hub_identity_and_stays_unindexed(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('admin.dashboard'));

        $response->assertSee('adm-body', false);
        $response->assertSee('adm-sidebar', false);
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $response->assertDontSee('bg-ccs-black', false);
    }
}
