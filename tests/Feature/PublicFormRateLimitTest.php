<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicFormRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->event = Event::factory()->create(['status' => EventStatus::Published]);
    }

    private function ticketRequest(string $email, ?Event $event = null): array
    {
        $event ??= $this->event;
        $ticketType = TicketType::factory()->for($event)->create();

        return ['ticket_type_id' => $ticketType->id, 'accept_terms' => '1', 'name' => 'Sara Ali', 'email' => $email, 'phone' => '+201001234567'];
    }

    public function test_the_ticket_form_is_limited_to_five_attempts_a_minute(): void
    {
        $url = route('ticket-requests.store', $this->event);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson($url, [])->assertStatus(422);
        }

        $this->postJson($url, [])
            ->assertStatus(429)
            ->assertJson(['message' => __('Too many attempts. Please wait a minute and try again.')]);
    }

    public function test_the_ticket_form_is_limited_to_twenty_attempts_an_hour(): void
    {
        $url = route('ticket-requests.store', $this->event);

        for ($minute = 0; $minute < 4; $minute++) {
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $this->postJson($url, [])->assertStatus(422);
            }
            $this->travel(61)->seconds();
        }

        $this->postJson($url, [])->assertStatus(429);
    }

    public function test_one_email_can_request_at_most_three_tickets_per_event_per_day(): void
    {
        $url = route('ticket-requests.store', $this->event);

        for ($request = 0; $request < 3; $request++) {
            $this->postJson($url, $this->ticketRequest('sara@example.com'))->assertOk();
        }

        $this->postJson($url, $this->ticketRequest('SARA@example.com'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
        $this->assertSame(3, $this->event->tickets()->count());
    }

    public function test_the_email_limit_is_per_event_and_per_address(): void
    {
        $url = route('ticket-requests.store', $this->event);
        for ($request = 0; $request < 3; $request++) {
            $this->postJson($url, $this->ticketRequest('sara@example.com'))->assertOk();
        }
        $this->travel(61)->seconds();

        $this->postJson($url, $this->ticketRequest('omar@example.com'))->assertOk();

        $otherEvent = Event::factory()->create(['status' => EventStatus::Published]);
        $this->postJson(route('ticket-requests.store', $otherEvent), $this->ticketRequest('sara@example.com', $otherEvent))->assertOk();
    }

    public function test_failed_submissions_do_not_count_towards_the_email_limit(): void
    {
        $url = route('ticket-requests.store', $this->event);
        $invalid = ['email' => 'sara@example.com'];

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->postJson($url, $invalid)->assertStatus(422);
        }
        $this->travel(61)->seconds();

        $this->postJson($url, $this->ticketRequest('sara@example.com'))->assertOk();
    }

    public function test_the_speaker_form_sends_the_visitor_back_with_a_message_when_limited(): void
    {
        $url = route('speaker-requests.store', $this->event);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('speaker-requests.create', $this->event))->post($url, ['name_en' => 'Kept'])->assertRedirect();
        }

        $this->from(route('speaker-requests.create', $this->event))->post($url, ['name_en' => 'Kept'])
            ->assertRedirect(route('speaker-requests.create', $this->event))
            ->assertSessionHasErrors(['email' => __('Too many attempts. Please wait a minute and try again.')])
            ->assertSessionHasInput('name_en', 'Kept');
    }

    public function test_the_sponsor_form_is_limited(): void
    {
        $url = route('sponsor-requests.store', $this->event);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post($url, []);
        }

        $this->post($url, [])->assertSessionHasErrors(['email' => __('Too many attempts. Please wait a minute and try again.')]);
    }

    public function test_the_contact_and_newsletter_forms_are_limited(): void
    {
        foreach ([route('contact.store', $this->event), route('contact.store.general'), route('newsletter.store', $this->event)] as $url) {
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $this->post($url, []);
            }

            // Translated after the requests: the visitor's locale is only known once one has run.
            $this->post($url, [])->assertSessionHasErrors(['email' => __('Too many attempts. Please wait a minute and try again.')]);
            $this->travel(61)->seconds();
        }
    }
}
