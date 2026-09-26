<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\TicketStatus;
use App\Models\ContactMessage;
use App\Models\Event;
use App\Models\Invitation;
use App\Models\InvitationRequest;
use App\Models\NewsletterSubscriber;
use App\Models\SpeakerRequest;
use App\Models\SponsorRequest;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaginationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->event = Event::factory()->create();
    }

    public function test_the_ticket_queue_shows_fifty_per_page_and_keeps_the_status_filter(): void
    {
        Ticket::factory()->for($this->event)->count(51)->create(['status' => TicketStatus::Pending]);

        $page = $this->actingAs($this->admin)->get(route('admin.events.ticket-requests.index', ['event' => $this->event, 'status' => 'pending']));

        $page->assertOk();
        $this->assertCount(50, $page->viewData('tickets'));
        $page->assertSee('status=pending&amp;page=2', false);

        $second = $this->actingAs($this->admin)->get(route('admin.events.ticket-requests.index', ['event' => $this->event, 'status' => 'pending', 'page' => 2]));
        $this->assertCount(1, $second->viewData('tickets'));
    }

    public function test_the_other_request_queues_are_paginated(): void
    {
        SpeakerRequest::factory()->for($this->event)->count(51)->create();
        SponsorRequest::factory()->for($this->event)->count(51)->create();
        $ticketType = TicketType::factory()->for($this->event)->create();
        InvitationRequest::factory()->count(51)->for($this->event)->create([
            'invitation_id' => fn () => Invitation::factory()->for($this->event)->for($ticketType),
        ]);

        foreach ([
            'admin.events.speaker-requests.index' => 'speakerRequests',
            'admin.events.sponsor-requests.index' => 'sponsorRequests',
            'admin.events.invitation-requests.index' => 'invitationRequests',
            'admin.events.invitations.index' => 'invitations',
        ] as $route => $variable) {
            $page = $this->actingAs($this->admin)->get(route($route, ['event' => $this->event, 'status' => 'all']));

            $page->assertOk();
            $this->assertCount(50, $page->viewData($variable), $route);
            $page->assertSee('page=2', false);
        }
    }

    public function test_contact_messages_are_paginated(): void
    {
        ContactMessage::factory()->for($this->event)->count(51)->create();

        $page = $this->actingAs($this->admin)->get(route('admin.events.contact-messages.index', $this->event));

        $page->assertOk();
        $this->assertCount(50, $page->viewData('contactMessages'));
    }

    public function test_newsletter_subscribers_are_paginated_a_hundred_at_a_time(): void
    {
        NewsletterSubscriber::factory()->for($this->event)->count(101)->create();

        $page = $this->actingAs($this->admin)->get(route('admin.events.newsletter-subscribers.index', $this->event));

        $page->assertOk();
        $this->assertCount(100, $page->viewData('newsletterSubscribers'));
        $page->assertSee('page=2', false);
    }

    public function test_a_short_list_shows_no_page_links(): void
    {
        Ticket::factory()->for($this->event)->count(3)->create(['status' => TicketStatus::Pending]);

        $this->actingAs($this->admin)->get(route('admin.events.ticket-requests.index', $this->event))
            ->assertOk()
            ->assertDontSee('page=2', false);
    }
}
