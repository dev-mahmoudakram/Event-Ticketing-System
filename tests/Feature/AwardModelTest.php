<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\VotingMode;
use App\Models\Award;
use App\Models\AwardNominee;
use App\Models\AwardVote;
use App\Models\Event;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AwardModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_award_lists_its_nominees_in_order(): void
    {
        $award = Award::factory()->create();
        $second = AwardNominee::factory()->for($award)->create(['sort_order' => 2]);
        $first = AwardNominee::factory()->for($award)->create(['sort_order' => 1]);

        $this->assertSame([$first->id, $second->id], $award->nominees()->pluck('id')->all());
    }

    public function test_one_email_votes_once_per_award(): void
    {
        $award = Award::factory()->create();
        $nominee = AwardNominee::factory()->for($award)->create();

        AwardVote::factory()->for($award)->for($nominee, 'nominee')->create(['email' => 'voter@example.com']);

        $this->expectException(QueryException::class);
        AwardVote::factory()->for($award)->for($nominee, 'nominee')->create(['email' => 'voter@example.com']);
    }

    public function test_the_same_email_votes_in_another_award(): void
    {
        $event = Event::factory()->create();
        $awards = Award::factory()->count(2)->for($event)->create();

        foreach ($awards as $award) {
            $nominee = AwardNominee::factory()->for($award)->create();
            AwardVote::factory()->for($award)->for($nominee, 'nominee')->create(['email' => 'voter@example.com']);
        }

        $this->assertSame(2, AwardVote::count());
    }

    public function test_vote_emails_are_stored_lowercase_and_trimmed(): void
    {
        $award = Award::factory()->create();
        $nominee = AwardNominee::factory()->for($award)->create();

        $vote = AwardVote::factory()->for($award)->for($nominee, 'nominee')->create(['email' => ' Voter@X.com ']);
        $this->assertSame('voter@x.com', $vote->fresh()->makeVisible('email')->email);

        $this->expectException(QueryException::class);
        AwardVote::factory()->for($award)->for($nominee, 'nominee')->create(['email' => 'voter@x.com']);
    }

    public function test_removed_votes_do_not_count(): void
    {
        $award = Award::factory()->create();
        $nominee = AwardNominee::factory()->for($award)->create();

        AwardVote::factory()->for($award)->for($nominee, 'nominee')->create();
        $removed = AwardVote::factory()->removed()->for($award)->for($nominee, 'nominee')->create();

        $this->assertSame(1, AwardVote::counted()->count());
        $this->assertTrue($removed->isRemoved());
    }

    public function test_the_manual_mode_overrides_the_schedule(): void
    {
        $event = Event::factory()->create([
            'voting_opens_at' => now()->subDays(3),
            'voting_closes_at' => now()->subDay(),
            'voting_mode' => VotingMode::Open,
        ]);
        $this->assertTrue($event->votingIsOpen());

        $event->update([
            'voting_opens_at' => now()->subDay(),
            'voting_closes_at' => now()->addDay(),
            'voting_mode' => VotingMode::Closed,
        ]);
        $this->assertFalse($event->fresh()->votingIsOpen());

        $event->update(['voting_opens_at' => null, 'voting_closes_at' => null, 'voting_mode' => VotingMode::Schedule]);
        $this->assertFalse($event->fresh()->votingIsOpen());
    }

    public function test_deleting_a_nominee_deletes_its_votes(): void
    {
        $award = Award::factory()->create();
        $nominee = AwardNominee::factory()->for($award)->create();
        AwardVote::factory()->for($award)->for($nominee, 'nominee')->create();

        $nominee->delete();

        $this->assertSame(0, AwardVote::count());
    }
}
