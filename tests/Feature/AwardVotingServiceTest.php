<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\VotingMode;
use App\Exceptions\VotingRefused;
use App\Mail\AwardVoteCodeMail;
use App\Models\Award;
use App\Models\AwardNominee;
use App\Models\AwardVote;
use App\Models\AwardVoteCode;
use App\Models\Event;
use App\Services\AwardVoting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Tests\TestCase;

class AwardVotingServiceTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private Award $award;

    private AwardNominee $nominee;

    private AwardVoting $voting;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->event = Event::factory()->create(['voting_mode' => VotingMode::Open]);
        $this->award = Award::factory()->for($this->event)->create();
        $this->nominee = AwardNominee::factory()->for($this->award)->create();
        $this->voting = app(AwardVoting::class);
    }

    public function test_sending_a_code_mails_it_and_stores_it_hashed(): void
    {
        $this->voting->sendCode($this->award, $this->nominee, 'voter@example.com', '10.0.0.1');

        $code = $this->lastMailedCode();
        $stored = AwardVoteCode::sole();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertNotSame($code, $stored->code_hash);
        $this->assertTrue(Hash::check($code, $stored->code_hash));
        $this->assertSame('voter@example.com', $stored->email);
        $this->assertSame($this->nominee->id, $stored->award_nominee_id);
        Mail::assertSent(AwardVoteCodeMail::class, fn (AwardVoteCodeMail $mail): bool => $mail->hasTo('voter@example.com'));
    }

    public function test_a_correct_code_records_one_vote_with_the_ip(): void
    {
        $this->voting->sendCode($this->award, $this->nominee, 'voter@example.com', '10.0.0.1');

        $vote = $this->voting->confirm($this->award, 'voter@example.com', $this->lastMailedCode(), '10.0.0.2');

        $this->assertSame(1, AwardVote::count());
        $this->assertSame('10.0.0.2', $vote->ip_address);
        $this->assertSame($this->event->id, $vote->event_id);
        $this->assertSame($this->nominee->id, $vote->award_nominee_id);
        $this->assertSame(0, AwardVoteCode::count());
    }

    public function test_email_case_and_spaces_are_the_same_voter(): void
    {
        $this->voting->sendCode($this->award, $this->nominee, 'Sara@X.com ', '10.0.0.1');
        $vote = $this->voting->confirm($this->award, 'Sara@X.com ', $this->lastMailedCode(), '10.0.0.1');

        $this->assertSame('sara@x.com', $vote->email);

        $this->expectExceptionObject(new VotingRefused(__('This email has already voted in this award.')));
        $this->voting->sendCode($this->award, $this->nominee, 'sara@x.com', '10.0.0.1');
    }

    public function test_a_second_confirmation_is_refused_cleanly(): void
    {
        AwardVoteCode::create([
            'award_id' => $this->award->id,
            'award_nominee_id' => $this->nominee->id,
            'email' => 'voter@example.com',
            'code_hash' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'ip_address' => '10.0.0.1',
        ]);
        AwardVote::factory()->for($this->award)->for($this->nominee, 'nominee')->create(['email' => 'voter@example.com']);

        try {
            $this->voting->confirm($this->award, 'voter@example.com', '123456', '10.0.0.1');
            $this->fail('A second vote for the same email was accepted.');
        } catch (VotingRefused $refused) {
            $this->assertSame(__('This email has already voted in this award.'), $refused->getMessage());
        }

        $this->assertSame(1, AwardVote::count());
    }

    public function test_a_removed_vote_keeps_the_email_blocked(): void
    {
        AwardVote::factory()->removed()->for($this->award)->for($this->nominee, 'nominee')->create(['email' => 'voter@example.com']);

        try {
            $this->voting->sendCode($this->award, $this->nominee, 'voter@example.com', '10.0.0.1');
            $this->fail('A removed vote did not keep the email blocked.');
        } catch (VotingRefused $refused) {
            $this->assertSame(__('This email has already voted in this award.'), $refused->getMessage());
        }

        $this->assertSame(0, $this->voting->counts($this->event)[$this->nominee->id]);
        Mail::assertNothingSent();
    }

    public function test_a_wrong_code_counts_attempts_and_the_fifth_kills_it(): void
    {
        $this->voting->sendCode($this->award, $this->nominee, 'voter@example.com', '10.0.0.1');
        $code = $this->lastMailedCode();
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($try = 1; $try <= 5; $try++) {
            try {
                $this->voting->confirm($this->award, 'voter@example.com', $wrong, '10.0.0.1');
                $this->fail('A wrong code was accepted.');
            } catch (VotingRefused $refused) {
                $this->assertSame(__('That code is not right.'), $refused->getMessage());
            }
        }

        $this->assertSame(5, AwardVoteCode::sole()->attempts);

        $this->expectExceptionObject(new VotingRefused(__('That code has expired. Request a new one.')));
        $this->voting->confirm($this->award, 'voter@example.com', $code, '10.0.0.1');
    }

    public function test_a_code_expires_after_ten_minutes(): void
    {
        $this->voting->sendCode($this->award, $this->nominee, 'voter@example.com', '10.0.0.1');
        $code = $this->lastMailedCode();

        $this->travel(11)->minutes();

        $this->expectExceptionObject(new VotingRefused(__('That code has expired. Request a new one.')));
        $this->voting->confirm($this->award, 'voter@example.com', $code, '10.0.0.1');
    }

    public function test_a_new_code_cancels_the_old_one(): void
    {
        $this->voting->sendCode($this->award, $this->nominee, 'voter@example.com', '10.0.0.1');
        $oldCode = $this->lastMailedCode();
        $this->voting->sendCode($this->award, $this->nominee, 'voter@example.com', '10.0.0.1');
        $newCode = $this->lastMailedCode();

        $this->assertSame(1, AwardVoteCode::count());

        if ($oldCode !== $newCode) {
            try {
                $this->voting->confirm($this->award, 'voter@example.com', $oldCode, '10.0.0.1');
                $this->fail('An old code still worked after a new one was sent.');
            } catch (VotingRefused $refused) {
                $this->assertSame(__('That code is not right.'), $refused->getMessage());
            }
        }

        $this->voting->confirm($this->award, 'voter@example.com', $newCode, '10.0.0.1');
        $this->assertSame(1, AwardVote::count());
    }

    public function test_a_code_cannot_be_used_after_voting_closes(): void
    {
        $this->voting->sendCode($this->award, $this->nominee, 'voter@example.com', '10.0.0.1');
        $code = $this->lastMailedCode();

        $this->event->update(['voting_mode' => VotingMode::Closed]);

        try {
            $this->voting->confirm($this->award->fresh(), 'voter@example.com', $code, '10.0.0.1');
            $this->fail('A code was accepted after voting closed.');
        } catch (VotingRefused $refused) {
            $this->assertSame(__('Voting is not open.'), $refused->getMessage());
        }

        $this->assertSame(0, AwardVote::count());
    }

    public function test_sending_a_code_is_refused_while_voting_is_closed(): void
    {
        $this->event->update(['voting_mode' => VotingMode::Closed]);

        $this->expectExceptionObject(new VotingRefused(__('Voting is not open.')));
        $this->voting->sendCode($this->award->fresh(), $this->nominee, 'voter@example.com', '10.0.0.1');
    }

    public function test_an_email_can_ask_for_three_codes_per_ten_minutes(): void
    {
        for ($request = 1; $request <= 3; $request++) {
            $this->voting->sendCode($this->award, $this->nominee, 'voter@example.com', '10.0.0.1');
        }

        try {
            $this->voting->sendCode($this->award, $this->nominee, 'Voter@Example.com', '10.0.0.9');
            $this->fail('A fourth code within ten minutes was sent.');
        } catch (VotingRefused $refused) {
            $this->assertSame(__('Too many codes were requested for this email. Try again in a few minutes.'), $refused->getMessage());
        }

        Mail::assertSentCount(3);

        $this->travel(11)->minutes();
        $this->voting->sendCode($this->award, $this->nominee, 'voter@example.com', '10.0.0.1');
        Mail::assertSentCount(4);
    }

    public function test_the_code_limit_is_per_award(): void
    {
        $otherAward = Award::factory()->for($this->event)->create();
        $otherNominee = AwardNominee::factory()->for($otherAward)->create();

        for ($request = 1; $request <= 3; $request++) {
            $this->voting->sendCode($this->award, $this->nominee, 'voter@example.com', '10.0.0.1');
        }

        $this->voting->sendCode($otherAward, $otherNominee, 'voter@example.com', '10.0.0.1');

        Mail::assertSentCount(4);
    }

    public function test_a_nominee_from_another_award_is_rejected(): void
    {
        $otherNominee = AwardNominee::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->voting->sendCode($this->award, $otherNominee, 'voter@example.com', '10.0.0.1');
    }

    public function test_counts_cover_every_nominee(): void
    {
        $quiet = AwardNominee::factory()->for($this->award)->create();
        $otherAward = Award::factory()->for($this->event)->create();
        $otherNominee = AwardNominee::factory()->for($otherAward)->create();
        $otherEventNominee = AwardNominee::factory()->create();

        AwardVote::factory()->count(2)->for($this->award)->for($this->nominee, 'nominee')->create();
        AwardVote::factory()->removed()->for($this->award)->for($this->nominee, 'nominee')->create();
        AwardVote::factory()->for($otherAward)->for($otherNominee, 'nominee')->create();

        $counts = $this->voting->counts($this->event);

        $this->assertSame([
            $this->nominee->id => 2,
            $quiet->id => 0,
            $otherNominee->id => 1,
        ], $counts);
        $this->assertArrayNotHasKey($otherEventNominee->id, $counts);
    }

    public function test_the_code_email_shows_the_code_award_and_nominee(): void
    {
        $mail = new AwardVoteCodeMail('042917', $this->award, $this->nominee);

        $mail->assertHasSubject(__('Your voting code: :code', ['code' => '042917']));
        $mail->assertSeeInHtml('042917');
        $mail->assertSeeInHtml($this->award->name_en);
        $mail->assertSeeInHtml($this->nominee->name_en);
        $mail->assertSeeInHtml(__('This code expires in 10 minutes and works once.'));

        app()->setLocale('ar');
        $arabic = new AwardVoteCodeMail('042917', $this->award, $this->nominee);

        $arabic->assertSeeInHtml($this->award->name_ar);
        $arabic->assertSeeInHtml($this->nominee->name_ar);
        $arabic->assertSeeInHtml('dir="rtl"', false);
    }

    public function test_emails_are_normalized(): void
    {
        $this->assertSame('sara@x.com', AwardVoting::normalizeEmail('  Sara@X.COM '));
    }

    private function lastMailedCode(): string
    {
        $sent = Mail::sent(AwardVoteCodeMail::class);

        $this->assertNotEmpty($sent, 'No voting code was mailed.');

        return $sent->last()->code;
    }
}
