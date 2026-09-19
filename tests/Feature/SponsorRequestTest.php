<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\SponsorRequestStatus;
use App\Mail\SponsorRequestSubmitted;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SponsorRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(): array
    {
        return [
            'name_en' => 'Acme Interiors',
            'name_ar' => 'أكمي',
            'contact_name' => 'Jane Creator',
            'email' => 'jane@example.com',
            'phone' => '+201001234567',
            'logo' => UploadedFile::fake()->image('logo.png'),
            'website_url' => 'https://acme.example.com',
            'instagram_url' => 'https://instagram.com/acme',
            'facebook_url' => 'https://facebook.com/acme',
            'message' => 'We would like to sponsor the next event.',
        ];
    }

    public function test_the_form_page_renders(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);

        $response = $this->get(route('sponsor-requests.create', $event).'?lang=en');

        $response->assertOk();
        $response->assertSee('action="'.route('sponsor-requests.store', $event).'"', false);
    }

    public function test_draft_event_returns_404_on_the_form_page(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Draft]);

        $response = $this->get(route('sponsor-requests.create', $event));

        $response->assertStatus(404);
    }

    public function test_visitor_can_submit_a_sponsor_request(): void
    {
        Storage::fake('public');
        $event = Event::factory()->create(['status' => EventStatus::Published]);

        $response = $this->post(route('sponsor-requests.store', $event), $this->validPayload());

        $response->assertRedirect(route('sponsor-requests.create', $event));
        $response->assertSessionHas('sponsor_request_success', true);
        $this->assertDatabaseHas('sponsor_requests', [
            'event_id' => $event->id,
            'name_en' => 'Acme Interiors',
            'name_ar' => 'أكمي',
            'contact_name' => 'Jane Creator',
            'email' => 'jane@example.com',
            'status' => SponsorRequestStatus::Pending->value,
        ]);
        $sponsorRequest = $event->sponsorRequests()->first();
        Storage::disk('public')->assertExists($sponsorRequest->logo_path);
    }

    public function test_a_confirmation_email_is_sent_on_submission(): void
    {
        Mail::fake();
        Storage::fake('public');
        $event = Event::factory()->create(['status' => EventStatus::Published]);

        $this->post(route('sponsor-requests.store', $event), $this->validPayload());

        Mail::assertSent(SponsorRequestSubmitted::class, fn ($mail) => $mail->hasTo('jane@example.com'));
    }

    public function test_the_success_message_shows_after_submission(): void
    {
        Storage::fake('public');
        $event = Event::factory()->create(['status' => EventStatus::Published]);

        $this->post(route('sponsor-requests.store', $event), $this->validPayload());
        $response = $this->get(route('sponsor-requests.create', $event).'?lang=en');

        $response->assertSee("Thanks — we'll review your request and be in touch soon.");
    }

    public function test_only_website_url_is_optional(): void
    {
        Storage::fake('public');
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $payload = $this->validPayload();
        unset($payload['website_url']);

        $response = $this->post(route('sponsor-requests.store', $event), $payload);

        $response->assertRedirect(route('sponsor-requests.create', $event));
        $this->assertDatabaseHas('sponsor_requests', ['event_id' => $event->id, 'website_url' => null]);
    }

    public function test_bilingual_name_is_required(): void
    {
        Storage::fake('public');
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $payload = $this->validPayload();
        $payload['name_en'] = '';
        $payload['name_ar'] = '';

        $response = $this->post(route('sponsor-requests.store', $event), $payload);

        $response->assertSessionHasErrors(['name_en', 'name_ar']);
        $this->assertDatabaseCount('sponsor_requests', 0);
    }

    public function test_contact_name_is_required(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $payload = $this->validPayload();
        $payload['contact_name'] = '';

        $response = $this->post(route('sponsor-requests.store', $event), $payload);

        $response->assertSessionHasErrors('contact_name');
        $this->assertDatabaseCount('sponsor_requests', 0);
    }

    public function test_logo_is_required(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $payload = $this->validPayload();
        unset($payload['logo']);

        $response = $this->post(route('sponsor-requests.store', $event), $payload);

        $response->assertSessionHasErrors('logo');
        $this->assertDatabaseCount('sponsor_requests', 0);
    }

    public function test_instagram_url_is_required(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $payload = $this->validPayload();
        unset($payload['instagram_url']);

        $response = $this->post(route('sponsor-requests.store', $event), $payload);

        $response->assertSessionHasErrors('instagram_url');
        $this->assertDatabaseCount('sponsor_requests', 0);
    }

    public function test_facebook_url_is_required(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $payload = $this->validPayload();
        unset($payload['facebook_url']);

        $response = $this->post(route('sponsor-requests.store', $event), $payload);

        $response->assertSessionHasErrors('facebook_url');
        $this->assertDatabaseCount('sponsor_requests', 0);
    }

    public function test_message_is_required(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $payload = $this->validPayload();
        unset($payload['message']);

        $response = $this->post(route('sponsor-requests.store', $event), $payload);

        $response->assertSessionHasErrors('message');
        $this->assertDatabaseCount('sponsor_requests', 0);
    }

    public function test_email_must_be_valid(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $payload = $this->validPayload();
        $payload['email'] = 'not-an-email';

        $response = $this->post(route('sponsor-requests.store', $event), $payload);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('sponsor_requests', 0);
    }

    public function test_phone_must_be_valid(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $payload = $this->validPayload();
        $payload['phone'] = 'not-a-phone';

        $response = $this->post(route('sponsor-requests.store', $event), $payload);

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('sponsor_requests', 0);
    }

    public function test_website_url_must_be_a_valid_url(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $payload = $this->validPayload();
        $payload['website_url'] = 'not-a-url';

        $response = $this->post(route('sponsor-requests.store', $event), $payload);

        $response->assertSessionHasErrors('website_url');
        $this->assertDatabaseCount('sponsor_requests', 0);
    }

    public function test_instagram_and_facebook_must_be_urls_not_bare_usernames(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $payload = $this->validPayload();
        $payload['instagram_url'] = '@myhandle';
        $payload['facebook_url'] = 'myhandle';

        $response = $this->post(route('sponsor-requests.store', $event), $payload);

        $response->assertSessionHasErrors(['instagram_url', 'facebook_url']);
        $this->assertDatabaseCount('sponsor_requests', 0);
    }

    public function test_draft_event_returns_404_on_submit(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Draft]);

        $response = $this->post(route('sponsor-requests.store', $event), $this->validPayload());

        $response->assertStatus(404);
    }

    public function test_general_footer_no_longer_links_to_a_sponsor_form(): void
    {
        $response = $this->get(route('home').'?lang=en');

        $response->assertOk();
        $response->assertDontSee('Become a Sponsor');
    }

    public function test_event_header_links_to_the_sponsor_request_page(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('href="'.route('sponsor-requests.create', $event).'"', false);
    }
}
