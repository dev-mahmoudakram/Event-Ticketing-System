<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Award;
use App\Models\AwardNominee;
use App\Models\AwardVote;
use App\Models\Event;
use App\Services\SuspiciousVotes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SuspiciousVotesTest extends TestCase
{
    use RefreshDatabase;

    private Award $award;

    private AwardNominee $nominee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->award = Award::factory()->for(Event::factory())->create();
        $this->nominee = AwardNominee::factory()->for($this->award)->create();
    }

    public function test_five_votes_from_one_ip_within_an_hour_are_suspicious(): void
    {
        $start = Carbon::parse('2026-10-20 10:00:00');

        $this->votesFrom('10.0.0.4', $start, [0, 10, 20, 30]);
        $this->votesFrom('10.0.0.5', $start, [0, 15, 30, 45, 60]);
        $this->votesFrom('10.0.0.9', $start, [0, 45, 90, 135, 180]);

        $suspicious = new SuspiciousVotes;
        $ips = $suspicious->ipsFor($this->award);

        $this->assertSame(['10.0.0.5'], $ips->values()->all());

        $flagged = AwardVote::query()->where('ip_address', '10.0.0.5')->first();
        $spread = AwardVote::query()->where('ip_address', '10.0.0.9')->first();
        $this->assertTrue($suspicious->isSuspicious($flagged));
        $this->assertFalse($suspicious->isSuspicious($spread));
    }

    public function test_the_window_slides_rather_than_following_clock_hours(): void
    {
        $start = Carbon::parse('2026-10-20 10:40:00');

        // Two votes before 11:00 and three after it: no clock hour holds five, but 10:40–11:30 does.
        $this->votesFrom('10.0.0.7', $start, [0, 10, 30, 40, 50]);

        $this->assertSame(['10.0.0.7'], (new SuspiciousVotes)->ipsFor($this->award)->values()->all());
    }

    public function test_removed_votes_and_other_awards_do_not_count(): void
    {
        $start = Carbon::parse('2026-10-20 10:00:00');
        $this->votesFrom('10.0.0.8', $start, [0, 5, 10, 15]);
        AwardVote::factory()->removed()->create([
            'award_id' => $this->award->id, 'award_nominee_id' => $this->nominee->id,
            'ip_address' => '10.0.0.8', 'created_at' => $start->copy()->addMinutes(20),
        ]);
        AwardVote::factory()->create(['ip_address' => '10.0.0.8', 'created_at' => $start->copy()->addMinutes(25)]);

        $this->assertTrue((new SuspiciousVotes)->ipsFor($this->award)->isEmpty());
    }

    /**
     * @param  list<int>  $minutesAfterStart
     */
    private function votesFrom(string $ip, Carbon $start, array $minutesAfterStart): void
    {
        foreach ($minutesAfterStart as $minutes) {
            AwardVote::factory()->create([
                'award_id' => $this->award->id,
                'award_nominee_id' => $this->nominee->id,
                'ip_address' => $ip,
                'created_at' => $start->copy()->addMinutes($minutes),
            ]);
        }
    }
}
