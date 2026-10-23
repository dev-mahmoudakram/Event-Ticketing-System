<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\VotingMode;
use App\Mail\AwardVoteCodeMail;
use App\Models\Award;
use App\Models\AwardNominee;
use App\Models\AwardVote;
use App\Models\Event;
use App\Services\TurnstileVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class AwardVotingEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private Award $award;

    private AwardNominee $nominee;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->event = Event::factory()->create(['voting_mode' => VotingMode::Open]);
        $this->award = Award::factory()->for($this->event)->create();
        $this->nominee = AwardNominee::factory()->for($this->award)->create();
    }

    public function test_a_visitor_gets_a_code_and_votes(): void
    {
        $this->passCaptcha();

        $this->requestCode('voter@example.com')
            ->assertOk()
            ->assertJsonStructure(['message']);

        $this->castVote('voter@example.com', $this->lastMailedCode())
            ->assertOk()
            ->assertJson([
                'message' => __('Thank you! Your vote has been counted.'),
                'nominee_id' => $this->nominee->id,
                'count' => 1,
            ])
            ->assertSessionHas('awarded.'.$this->event->id, [$this->award->id]);

        $this->assertSame(1, AwardVote::query()->counted()->count());
    }

    public function test_a_failed_captcha_sends_no_code(): void
    {
        $this->mock(TurnstileVerifier::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verify')->once()->andReturn(false);
        });

        $this->requestCode('voter@example.com')
            ->assertStatus(422)
            ->assertJsonPath('errors.captcha.0', __('Please complete the security check.'));

        Mail::assertNothingSent();
    }

    public function test_a_nominee_from_another_award_is_not_found(): void
    {
        $this->passCaptcha();
        $otherNominee = AwardNominee::factory()->for(Award::factory()->for($this->event))->create();

        $this->requestCode('voter@example.com', $otherNominee)
            ->assertStatus(422)
            ->assertJsonValidationErrors('nominee_id');

        $this->castVote('voter@example.com', '123456', $otherNominee)
            ->assertStatus(422)
            ->assertJsonValidationErrors('nominee_id');

        Mail::assertNothingSent();
    }

    public function test_an_award_from_another_event_is_not_found(): void
    {
        $this->passCaptcha();
        $otherEvent = Event::factory()->create(['voting_mode' => VotingMode::Open]);

        $this->postJson(route('awards.code', [$otherEvent, $this->award]), [
            'nominee_id' => $this->nominee->id,
            'email' => 'voter@example.com',
            'cf-turnstile-response' => 'token',
        ])->assertNotFound();

        $this->postJson(route('awards.vote', [$otherEvent, $this->award]), [
            'nominee_id' => $this->nominee->id,
            'email' => 'voter@example.com',
            'code' => '123456',
        ])->assertNotFound();
    }

    public function test_voting_closed_is_refused(): void
    {
        $this->passCaptcha();
        $this->event->update(['voting_mode' => VotingMode::Closed]);

        $this->requestCode('voter@example.com')
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', __('Voting is not open.'));

        Mail::assertNothingSent();
    }

    public function test_a_wrong_code_is_reported_on_the_code_field(): void
    {
        $this->passCaptcha();
        $this->requestCode('voter@example.com')->assertOk();
        $wrongCode = $this->lastMailedCode() === '000000' ? '111111' : '000000';

        $this->castVote('voter@example.com', $wrongCode)
            ->assertStatus(422)
            ->assertJsonPath('errors.code.0', __('That code is not right.'))
            ->assertSessionMissing('awarded.'.$this->event->id);

        $this->assertSame(0, AwardVote::count());
    }

    public function test_a_code_for_another_choice_is_reported_on_the_code_field(): void
    {
        $this->passCaptcha();
        $otherChoice = AwardNominee::factory()->for($this->award)->create();
        $this->requestCode('voter@example.com')->assertOk();

        $this->castVote('voter@example.com', $this->lastMailedCode(), $otherChoice)
            ->assertStatus(422)
            ->assertJsonPath('errors.code.0', __('That code was sent for a different choice. Request a new one.'));
    }

    public function test_voting_twice_is_reported_on_the_email_field(): void
    {
        $this->passCaptcha();
        $this->requestCode('voter@example.com')->assertOk();
        $this->castVote('voter@example.com', $this->lastMailedCode())->assertOk();

        $this->requestCode('VOTER@example.com ')
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', __('This email has already voted in this award.'));
    }

    public function test_the_session_lists_each_award_once(): void
    {
        $this->passCaptcha();
        $secondAward = Award::factory()->for($this->event)->create();
        $secondNominee = AwardNominee::factory()->for($secondAward)->create();

        $this->requestCode('one@example.com')->assertOk();
        $this->castVote('one@example.com', $this->lastMailedCode())->assertOk();
        $this->requestCode('two@example.com')->assertOk();
        $this->castVote('two@example.com', $this->lastMailedCode())->assertOk();
        $this->requestCode('one@example.com', $secondNominee, $secondAward)->assertOk();

        $this->castVote('one@example.com', $this->lastMailedCode(), $secondNominee, $secondAward)
            ->assertOk()
            ->assertSessionHas('awarded.'.$this->event->id, [$this->award->id, $secondAward->id]);
    }

    public function test_a_mail_failure_is_a_friendly_error(): void
    {
        $this->passCaptcha();
        Mail::shouldReceive('to')->andThrow(new TransportException('Connection refused'));

        $this->requestCode('voter@example.com')
            ->assertStatus(503)
            ->assertJsonPath('message', __('We could not send the email. Please try again in a minute.'));
    }

    public function test_the_sixth_code_request_in_a_minute_is_throttled(): void
    {
        $this->passCaptcha();

        foreach (range(1, 5) as $attempt) {
            $this->requestCode("voter{$attempt}@example.com")->assertOk();
        }

        $this->requestCode('voter6@example.com')
            ->assertStatus(429)
            ->assertJsonPath('message', __('Too many attempts. Please wait a minute and try again.'));
    }

    public function test_the_eleventh_vote_attempt_in_a_minute_is_throttled(): void
    {
        foreach (range(1, 10) as $attempt) {
            $this->castVote("voter{$attempt}@example.com", '123456')->assertStatus(422);
        }

        $this->castVote('voter11@example.com', '123456')
            ->assertStatus(429)
            ->assertJsonPath('message', __('Too many attempts. Please wait a minute and try again.'));
    }

    public function test_many_people_on_one_ip_can_all_vote(): void
    {
        $this->passCaptcha();

        foreach (range(1, 10) as $person) {
            if ($person === 6) {
                $this->travel(61)->seconds();
            }

            $this->requestCode("person{$person}@example.com")->assertOk();
            $this->castVote("person{$person}@example.com", $this->lastMailedCode())->assertOk();
        }

        $this->assertSame(10, AwardVote::query()->counted()->where('award_id', $this->award->id)->count());
    }

    public function test_counts_are_public_json(): void
    {
        $quiet = AwardNominee::factory()->for($this->award)->create();
        AwardVote::factory()->count(2)->create([
            'event_id' => $this->event->id,
            'award_id' => $this->award->id,
            'award_nominee_id' => $this->nominee->id,
        ]);

        $this->getJson(route('awards.counts', $this->event))
            ->assertOk()
            ->assertExactJson([
                'counts' => [(string) $this->nominee->id => 2, (string) $quiet->id => 0],
                'open' => true,
            ]);

        $this->event->update(['voting_mode' => VotingMode::Closed]);

        $this->getJson(route('awards.counts', $this->event))->assertJsonPath('open', false);
    }

    public function test_counts_are_an_object_even_with_no_nominees(): void
    {
        $empty = Event::factory()->create();

        $response = $this->getJson(route('awards.counts', $empty))->assertOk();

        $this->assertSame('{"counts":{},"open":false}', $response->getContent());
    }

    public function test_a_draft_event_has_no_voting(): void
    {
        $this->passCaptcha();
        $this->event->update(['status' => EventStatus::Draft]);

        $this->getJson(route('awards.counts', $this->event))->assertNotFound();
        $this->requestCode('voter@example.com')->assertNotFound();
        $this->castVote('voter@example.com', '123456')->assertNotFound();

        Mail::assertNothingSent();
    }

    private function passCaptcha(): void
    {
        $this->mock(TurnstileVerifier::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verify')->andReturn(true);
        });
    }

    private function requestCode(string $email, ?AwardNominee $nominee = null, ?Award $award = null): TestResponse
    {
        return $this->postJson(route('awards.code', [$this->event, $award ?? $this->award]), [
            'nominee_id' => ($nominee ?? $this->nominee)->id,
            'email' => $email,
            'cf-turnstile-response' => 'token',
        ]);
    }

    private function castVote(string $email, string $code, ?AwardNominee $nominee = null, ?Award $award = null): TestResponse
    {
        return $this->postJson(route('awards.vote', [$this->event, $award ?? $this->award]), [
            'nominee_id' => ($nominee ?? $this->nominee)->id,
            'email' => $email,
            'code' => $code,
        ]);
    }

    private function lastMailedCode(): string
    {
        $sent = Mail::sent(AwardVoteCodeMail::class);

        $this->assertNotEmpty($sent, 'No voting code was mailed.');

        return $sent->last()->code;
    }
}
