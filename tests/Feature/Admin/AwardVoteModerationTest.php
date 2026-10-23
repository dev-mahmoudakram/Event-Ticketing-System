<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Permission;
use App\Enums\VotingMode;
use App\Exceptions\VotingRefused;
use App\Models\Award;
use App\Models\AwardNominee;
use App\Models\AwardVote;
use App\Models\Event;
use App\Models\User;
use App\Services\AwardVoting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AwardVoteModerationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Event $event;

    private Award $award;

    private AwardNominee $nominee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->event = Event::factory()->create(['voting_mode' => VotingMode::Open]);
        $this->award = Award::factory()->for($this->event)->create(['name_ar' => 'صانع المحتوى للعام', 'name_en' => 'Creator of the Year']);
        $this->nominee = AwardNominee::factory()->for($this->award)->create(['name_ar' => 'نورة أحمد', 'name_en' => 'Nora Ahmed']);
    }

    public function test_the_votes_list_filters_and_flags(): void
    {
        foreach (range(0, 4) as $minutes) {
            $this->voteFor($this->nominee, ['ip_address' => '10.0.0.5', 'created_at' => now()->subMinutes($minutes)]);
        }
        $this->voteFor($this->nominee, ['email' => 'calm@example.com', 'ip_address' => '10.0.0.6']);

        $otherNominee = AwardNominee::factory()->for($this->award)->create();
        $this->voteFor($otherNominee, ['email' => 'elsewhere@example.com']);

        $otherAward = Award::factory()->for($this->event)->create();
        $this->voteFor(AwardNominee::factory()->for($otherAward)->create(), ['email' => 'other-award@example.com']);

        $this->actingAs($this->admin)
            ->get(route('admin.events.award-votes.index', $this->event))
            ->assertOk()
            ->assertSee('calm@example.com')
            ->assertSee('other-award@example.com')
            ->assertSee('10.0.0.5')
            ->assertSee(__('Suspicious'));

        $this->actingAs($this->admin)
            ->get(route('admin.events.award-votes.index', [$this->event, 'award' => $this->award->id, 'nominee' => $this->nominee->id]))
            ->assertOk()
            ->assertSee('calm@example.com')
            ->assertSee('10.0.0.6')
            ->assertDontSee('elsewhere@example.com')
            ->assertDontSee('other-award@example.com');

        $this->actingAs($this->admin)
            ->get(route('admin.events.award-votes.index', [$this->event, 'award' => $otherAward->id]))
            ->assertOk()
            ->assertSee('other-award@example.com')
            ->assertDontSee('calm@example.com')
            ->assertDontSee(__('Suspicious'));
    }

    public function test_removing_a_vote_stops_counting_and_keeps_the_email_blocked(): void
    {
        Mail::fake();
        $vote = $this->voteFor($this->nominee, ['email' => 'voter@example.com']);
        $this->assertSame(1, $this->award->votes()->counted()->count());

        $this->actingAs($this->admin)
            ->delete(route('admin.events.award-votes.destroy', [$this->event, $vote]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $vote->refresh();
        $this->assertTrue($vote->isRemoved());
        $this->assertSame($this->admin->id, $vote->removed_by);
        $this->assertSame(0, $this->award->votes()->counted()->count());
        $this->assertSame(1, $this->award->votes()->count());

        $this->expectExceptionObject(new VotingRefused(__('This email has already voted in this award.')));
        app(AwardVoting::class)->sendCode($this->award, $this->nominee, 'voter@example.com', '10.0.0.1');
    }

    public function test_removing_all_votes_from_an_ip_in_an_award(): void
    {
        $fromIp = collect(range(1, 3))->map(fn (): AwardVote => $this->voteFor($this->nominee, ['ip_address' => '10.0.0.5']));
        $otherIp = $this->voteFor($this->nominee, ['ip_address' => '10.0.0.6']);

        $otherAward = Award::factory()->for($this->event)->create();
        $sameIpOtherAward = $this->voteFor(AwardNominee::factory()->for($otherAward)->create(), ['ip_address' => '10.0.0.5']);

        $this->actingAs($this->admin)
            ->post(route('admin.events.award-votes.destroy-ip', $this->event), ['award' => $this->award->id, 'ip' => '10.0.0.5'])
            ->assertRedirect()
            ->assertSessionHas('success');

        foreach ($fromIp as $vote) {
            $vote->refresh();
            $this->assertTrue($vote->isRemoved());
            $this->assertSame($this->admin->id, $vote->removed_by);
        }
        $this->assertFalse($otherIp->fresh()->isRemoved());
        $this->assertFalse($sameIpOtherAward->fresh()->isRemoved());
    }

    public function test_removing_by_ip_rejects_an_award_from_another_event(): void
    {
        $foreignAward = Award::factory()->create();
        $foreignVote = $this->voteFor(AwardNominee::factory()->for($foreignAward)->create(), ['ip_address' => '10.0.0.5']);

        $this->actingAs($this->admin)
            ->post(route('admin.events.award-votes.destroy-ip', $this->event), ['award' => $foreignAward->id, 'ip' => '10.0.0.5'])
            ->assertSessionHasErrors('award');

        $this->assertFalse($foreignVote->fresh()->isRemoved());
    }

    public function test_export_lists_voters_with_a_bom(): void
    {
        $this->voteFor($this->nominee, ['email' => 'voter@example.com', 'ip_address' => '10.0.0.5']);
        $this->voteFor($this->nominee, ['email' => '=cmd@example.com']);
        AwardVote::factory()->removed()->create([
            'award_id' => $this->award->id, 'award_nominee_id' => $this->nominee->id, 'email' => 'gone@example.com',
        ]);
        $otherAward = Award::factory()->for($this->event)->create();
        $this->voteFor(AwardNominee::factory()->for($otherAward)->create(), ['email' => 'other-award@example.com']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.events.award-votes.export', $this->event))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('voter@example.com', $csv);
        $this->assertStringContainsString($this->nominee->name(), $csv);
        $this->assertStringContainsString($this->award->name(), $csv);
        $this->assertStringContainsString('10.0.0.5', $csv);
        $this->assertStringContainsString("'=cmd@example.com", $csv);
        $this->assertStringContainsString('other-award@example.com', $csv);
        $statusByEmail = collect(explode("\n", trim(substr($csv, 3))))
            ->map(fn (string $line): array => str_getcsv($line))
            ->mapWithKeys(fn (array $row): array => [$row[0] => $row[5]]);
        $this->assertSame(__('Removed'), $statusByEmail['gone@example.com']);
        $this->assertSame(__('Counted'), $statusByEmail['voter@example.com']);

        $filtered = $this->actingAs($this->admin)
            ->get(route('admin.events.award-votes.export', [$this->event, 'award' => $this->award->id]))
            ->streamedContent();
        $this->assertStringContainsString('voter@example.com', $filtered);
        $this->assertStringNotContainsString('other-award@example.com', $filtered);
    }

    public function test_another_events_vote_is_not_found(): void
    {
        $foreignVote = AwardVote::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.events.award-votes.destroy', [$this->event, $foreignVote]))
            ->assertNotFound();

        $this->assertFalse($foreignVote->fresh()->isRemoved());
    }

    public function test_a_removed_vote_can_be_restored_and_counts_again(): void
    {
        $vote = AwardVote::factory()->removed()->create([
            'award_id' => $this->award->id, 'award_nominee_id' => $this->nominee->id, 'email' => 'voter@example.com',
            'ip_address' => '10.0.0.5',
        ]);
        $this->assertSame(0, $this->award->votes()->counted()->count());

        $this->actingAs($this->admin)
            ->get(route('admin.events.award-votes.index', $this->event))
            ->assertOk()
            ->assertSee(route('admin.events.award-votes.restore', [$this->event, $vote]), false)
            ->assertSee(route('admin.events.award-votes.restore-ip', $this->event), false)
            ->assertSee(__('Restore all from this IP'))
            ->assertSee(__('Removed votes can be restored; they count again.'));

        $this->actingAs($this->admin)
            ->patch(route('admin.events.award-votes.restore', [$this->event, $vote]))
            ->assertRedirect()
            ->assertSessionHas('success', __('Vote restored. It counts again.'));

        $vote->refresh();
        $this->assertFalse($vote->isRemoved());
        $this->assertNull($vote->removed_by);
        $this->assertSame(1, $this->award->votes()->counted()->count());
        $this->assertSame(1, $this->award->votes()->count());
    }

    public function test_restoring_all_votes_from_an_ip_in_an_award(): void
    {
        $removedFromIp = collect(range(1, 3))->map(fn (): AwardVote => AwardVote::factory()->removed()->create([
            'award_id' => $this->award->id, 'award_nominee_id' => $this->nominee->id, 'ip_address' => '10.0.0.5',
        ]));
        $removedOtherIp = AwardVote::factory()->removed()->create([
            'award_id' => $this->award->id, 'award_nominee_id' => $this->nominee->id, 'ip_address' => '10.0.0.6',
        ]);
        $otherAward = Award::factory()->for($this->event)->create();
        $removedSameIpOtherAward = AwardVote::factory()->removed()->create([
            'award_id' => $otherAward->id,
            'award_nominee_id' => AwardNominee::factory()->for($otherAward)->create()->id,
            'ip_address' => '10.0.0.5',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.events.award-votes.restore-ip', $this->event), ['award' => $this->award->id, 'ip' => '10.0.0.5'])
            ->assertRedirect()
            ->assertSessionHas('success', __('Votes restored from this IP: :count', ['count' => 3]));

        foreach ($removedFromIp as $vote) {
            $vote->refresh();
            $this->assertFalse($vote->isRemoved());
            $this->assertNull($vote->removed_by);
        }
        $this->assertTrue($removedOtherIp->fresh()->isRemoved());
        $this->assertTrue($removedSameIpOtherAward->fresh()->isRemoved());
        $this->assertSame(3, $this->award->votes()->counted()->count());
    }

    public function test_restoring_by_ip_rejects_an_award_from_another_event(): void
    {
        $foreignAward = Award::factory()->create();
        $foreignVote = AwardVote::factory()->removed()->create([
            'award_id' => $foreignAward->id,
            'award_nominee_id' => AwardNominee::factory()->for($foreignAward)->create()->id,
            'ip_address' => '10.0.0.5',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.events.award-votes.restore-ip', $this->event), ['award' => $foreignAward->id, 'ip' => '10.0.0.5'])
            ->assertSessionHasErrors('award');

        $this->assertTrue($foreignVote->fresh()->isRemoved());
    }

    public function test_another_events_vote_cannot_be_restored(): void
    {
        $foreignVote = AwardVote::factory()->removed()->create();

        $this->actingAs($this->admin)
            ->patch(route('admin.events.award-votes.restore', [$this->event, $foreignVote]))
            ->assertNotFound();

        $this->assertTrue($foreignVote->fresh()->isRemoved());
    }

    public function test_the_sidebar_highlights_awards_on_the_votes_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.events.award-votes.index', $this->event))
            ->assertSee('href="'.route('admin.events.awards.index', $this->event).'" class="adm-nav-link adm-nav-sub is-active"', false);
    }

    public function test_the_awards_permission_opens_the_votes(): void
    {
        $this->actingAs(User::factory()->withPermissions(Permission::Awards)->create())
            ->get(route('admin.events.award-votes.index', $this->event))->assertOk();
        $this->actingAs(User::factory()->withPermissions(Permission::Speakers)->create())
            ->get(route('admin.events.award-votes.index', $this->event))->assertForbidden();
        $this->actingAs(User::factory()->withPermissions(Permission::Speakers)->create())
            ->get(route('admin.events.award-votes.export', $this->event))->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function voteFor(AwardNominee $nominee, array $attributes = []): AwardVote
    {
        return AwardVote::factory()->create($attributes + [
            'award_id' => $nominee->award_id,
            'award_nominee_id' => $nominee->id,
        ]);
    }
}
