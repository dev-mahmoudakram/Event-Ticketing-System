<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\SpeakerRequestStatus;
use App\Mail\SpeakerRequestSubmitted;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpeakerRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(): array
    {
        return [
            'name_en' => 'Jane Creator',
            'name_ar' => 'جين',
            'title_en' => 'Product Designer',
            'title_ar' => 'مصممة منتجات',
            'bio_en' => 'Jane has spent a decade designing interiors.',
            'bio_ar' => 'قضت جين عقدًا في تصميم الديكورات الداخلية.',
            'photo' => UploadedFile::fake()->image('photo.png'),
            'email' => 'jane@example.com',
            'phone' => '+201001234567',
            'message' => 'I would like to speak about sustainable materials.',
        ];
    }

    public function test_the_form_page_renders(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);

        $response = $this->get(route('speaker-requests.create', $event).'?lang=en');

        $response->assertOk();
        $response->assertSee('action="'.route('speaker-requests.store', $event).'"', false);
    }

    public function test_draft_event_returns_404_on_the_form_page(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Draft]);

        $response = $this->get(route('speaker-requests.create', $event));

        $response->assertStatus(404);
    }

    public function test_visitor_can_submit_a_speaker_request(): void
    {
        Storage::fake('public');
        $event = Event::factory()->create(['status' => EventStatus::Published]);

        $response = $this->post(route('speaker-requests.store', $event), $this->validPayload());

        $response->assertRedirect(route('speaker-requests.create', $event));
        $response->assertSessionHas('speaker_request_success', true);
        $this->assertDatabaseHas('speaker_requests', [
            'event_id' => $event->id,
            'name_en' => 'Jane Creator',
            'name_ar' => 'جين',
            'email' => 'jane@example.com',
            'status' => SpeakerRequestStatus::Pending->value,
        ]);
        $speakerRequest = $event->speakerRequests()->first();
        Storage::disk('public')->assertExists($speakerRequest->photo_path);
    }

    public function test_the_success_message_shows_after_submission(): void
    {
        Storage::fake('public');
        $event = Event::factory()->create(['status' => EventStatus::Published]);

        $this->post(route('speaker-requests.store', $event), $this->validPayload());
        $response = $this->get(route('speaker-requests.create', $event).'?lang=en');

        $response->assertSee("Thanks — we'll review your request and be in touch soon.");
    }

    public function test_a_confirmation_email_is_sent_on_submission(): void
    {
        Mail::fake();
        Storage::fake('public');
        $event = Event::factory()->create(['status' => EventStatus::Published]);

        $this->post(route('speaker-requests.store', $event), $this->validPayload());

        Mail::assertSent(SpeakerRequestSubmitted::class, fn ($mail) => $mail->hasTo('jane@example.com'));
    }

    public function test_bilingual_name_is_required(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $payload = $this->validPayload();
        $payload['name_en'] = '';
        $payload['name_ar'] = '';

        $response = $this->post(route('speaker-requests.store', $event), $payload);

        $response->assertSessionHasErrors(['name_en', 'name_ar']);
        $this->assertDatabaseCount('speaker_requests', 0);
    }

    public function test_bilingual_title_is_required(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $payload = $this->validPayload();
        $payload['title_en'] = '';
        $payload['title_ar'] = '';

        $response = $this->post(route('speaker-requests.store', $event), $payload);

        $response->assertSessionHasErrors(['title_en', 'title_ar']);
        $this->assertDatabaseCount('speaker_requests', 0);
    }

    public function test_bilingual_bio_is_required(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $payload = $this->validPayload();
        $payload['bio_en'] = '';
        $payload['bio_ar'] = '';

        $response = $this->post(route('speaker-requests.store', $event), $payload);

        $response->assertSessionHasErrors(['bio_en', 'bio_ar']);
        $this->assertDatabaseCount('speaker_requests', 0);
    }

    public function test_photo_is_required(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $payload = $this->validPayload();
        unset($payload['photo']);

        $response = $this->post(route('speaker-requests.store', $event), $payload);

        $response->assertSessionHasErrors('photo');
        $this->assertDatabaseCount('speaker_requests', 0);
    }

    public function test_message_is_required(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $payload = $this->validPayload();
        unset($payload['message']);

        $response = $this->post(route('speaker-requests.store', $event), $payload);

        $response->assertSessionHasErrors('message');
        $this->assertDatabaseCount('speaker_requests', 0);
    }

    public function test_email_must_be_valid(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $payload = $this->validPayload();
        $payload['email'] = 'not-an-email';

        $response = $this->post(route('speaker-requests.store', $event), $payload);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('speaker_requests', 0);
    }

    public function test_phone_must_be_valid(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);
        $payload = $this->validPayload();
        $payload['phone'] = 'not-a-phone';

        $response = $this->post(route('speaker-requests.store', $event), $payload);

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('speaker_requests', 0);
    }

    public function test_draft_event_returns_404_on_submit(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Draft]);

        $response = $this->post(route('speaker-requests.store', $event), $this->validPayload());

        $response->assertStatus(404);
    }

    public function test_speakers_section_links_to_the_speaker_request_page(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Published]);

        $response = $this->get(route('landing.show', $event).'?lang=en');

        $response->assertSee('href="'.route('speaker-requests.create', $event).'"', false);
    }
}
