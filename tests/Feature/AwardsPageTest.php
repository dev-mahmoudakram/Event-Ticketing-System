<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\VotingMode;
use App\Models\Award;
use App\Models\AwardNominee;
use App\Models\AwardVote;
use App\Models\Event;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AwardsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_awards_placeholder_page_renders(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('awards.show', $event));

        $response->assertStatus(200);
        $response->assertSee(__('Awards'));
    }

    public function test_the_page_shows_tabs_nominees_and_counts(): void
    {
        $event = Event::factory()->create(['voting_mode' => VotingMode::Open]);
        $first = Award::factory()->for($event)->create(['name_ar' => 'Creator of the Year', 'name_en' => 'Creator of the Year', 'sort_order' => 1]);
        $second = Award::factory()->for($event)->create(['name_ar' => 'Best Newcomer', 'name_en' => 'Best Newcomer', 'sort_order' => 2]);
        $sara = AwardNominee::factory()->for($first)->create(['name_ar' => 'Sara Ali', 'name_en' => 'Sara Ali']);
        AwardNominee::factory()->for($second)->create(['name_ar' => 'Omar Hany', 'name_en' => 'Omar Hany']);
        AwardVote::factory()->count(7)->create(['award_id' => $first->id, 'award_nominee_id' => $sara->id]);

        $response = $this->get(route('awards.show', $event));

        $response->assertOk()
            ->assertSee('role="tablist"', false)
            ->assertSeeInOrder(['Creator of the Year', 'Best Newcomer'])
            ->assertSee('Sara Ali')
            ->assertSee('Omar Hany')
            ->assertSee('data-count-for="'.$sara->id.'" data-count="7"', false)
            ->assertSee('id="award-'.$first->id.'"', false);
    }

    public function test_vote_counts_use_plural_forms(): void
    {
        $event = Event::factory()->create(['voting_mode' => VotingMode::Open]);
        $award = Award::factory()->for($event)->create();
        $counts = [1, 2, 3, 11];
        $nominees = [];

        foreach ($counts as $count) {
            $nominee = AwardNominee::factory()->for($award)->create();
            AwardVote::factory()->count($count)->create(['award_id' => $award->id, 'award_nominee_id' => $nominee->id]);
            $nominees[$count] = $nominee;
        }

        $countLabel = fn (int $count): string => 'data-count-for="'.$nominees[$count]->id.'" data-count="'.$count.'">';

        $this->withSession(['locale' => 'en'])->get(route('awards.show', $event))
            ->assertOk()
            ->assertSee($countLabel(1).'1 vote<', false)
            ->assertSee($countLabel(2).'2 votes<', false);

        $this->withSession(['locale' => 'ar'])->get(route('awards.show', $event))
            ->assertOk()
            ->assertSee($countLabel(3).'3 أصوات<', false)
            ->assertSee($countLabel(11).'11 صوتًا<', false);
    }

    public function test_vote_buttons_show_only_while_voting_is_open(): void
    {
        $event = Event::factory()->create(['voting_mode' => VotingMode::Open]);
        $award = Award::factory()->for($event)->create();
        AwardNominee::factory()->for($award)->create();

        $this->get(route('awards.show', $event))->assertOk()->assertSee('data-vote-nominee', false);

        $event->update(['voting_mode' => VotingMode::Closed]);

        $this->get(route('awards.show', $event))
            ->assertOk()
            ->assertDontSee('data-vote-nominee', false)
            ->assertSee(__('Voting is closed'));
    }

    public function test_the_status_line_follows_the_schedule(): void
    {
        $event = Event::factory()->create([
            'voting_mode' => VotingMode::Schedule,
            'voting_opens_at' => now()->addDays(2),
            'voting_closes_at' => now()->addDays(5),
        ]);

        $this->get(route('awards.show', $event))
            ->assertOk()
            ->assertSee(__('Voting opens on :date', ['date' => $event->voting_opens_at->translatedFormat('j F Y, g:i A')]));

        $event->update(['voting_opens_at' => now()->subDay()]);

        $this->get(route('awards.show', $event))
            ->assertOk()
            ->assertSee(__('Voting closes on :date', ['date' => $event->voting_closes_at->translatedFormat('j F Y, g:i A')]));
    }

    public function test_a_browser_that_voted_sees_you_voted(): void
    {
        $event = Event::factory()->create(['voting_mode' => VotingMode::Open]);
        $award = Award::factory()->for($event)->create();
        AwardNominee::factory()->for($award)->create();

        $this->withSession(['awarded.'.$event->id => [$award->id]])
            ->get(route('awards.show', $event))
            ->assertOk()
            ->assertSee(__('You voted'))
            ->assertDontSee('data-vote-nominee', false);
    }

    public function test_the_page_without_awards_says_so(): void
    {
        $event = Event::factory()->create();

        $this->get(route('awards.show', $event))
            ->assertOk()
            ->assertSee(__('Awards will be announced soon.'))
            ->assertDontSee('role="tablist"', false);
    }

    public function test_the_turnstile_site_key_is_on_the_page(): void
    {
        config(['services.turnstile.site_key' => 'test-site-key']);
        $event = Event::factory()->create(['voting_mode' => VotingMode::Open]);
        AwardNominee::factory()->for(Award::factory()->for($event))->create();

        $this->get(route('awards.show', $event))
            ->assertOk()
            ->assertSee('data-site-key="test-site-key"', false)
            ->assertSee('https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit', false)
            ->assertSee('data-counts-url="'.route('awards.counts', $event).'"', false)
            ->assertSee('__AWARD__', false)
            ->assertSee('name="csrf-token"', false);
    }

    public function test_the_awards_page_names_the_event(): void
    {
        $event = Event::factory()->create(['name_en' => 'Test Summit', 'name_ar' => 'قمة الاختبار']);

        $english = $this->awardsSection($this->withSession(['locale' => 'en'])->get(route('awards.show', $event))->assertOk()->getContent());
        $this->assertStringContainsString('Test Summit Awards', $english);
        $this->assertStringNotContainsString('CCS', $english);

        $arabic = $this->awardsSection($this->withSession(['locale' => 'ar'])->get(route('awards.show', $event))->assertOk()->getContent());
        $this->assertStringContainsString('جوائز قمة الاختبار', $arabic);
        $this->assertStringNotContainsString('CCS', $arabic);
    }

    public function test_the_strings_blob_explains_an_expired_page(): void
    {
        $event = Event::factory()->create(['voting_mode' => VotingMode::Open]);
        AwardNominee::factory()->for(Award::factory()->for($event))->create();

        $strings = $this->stringsBlob($this->withSession(['locale' => 'en'])->get(route('awards.show', $event))->getContent());
        $this->assertSame('This page has expired. Please reload the page and try again.', $strings['pageExpired'] ?? null);

        $strings = $this->stringsBlob($this->withSession(['locale' => 'ar'])->get(route('awards.show', $event))->getContent());
        $this->assertSame('انتهت صلاحية الصفحة. يرجى إعادة تحميل الصفحة والمحاولة مرة أخرى.', $strings['pageExpired'] ?? null);
    }

    public function test_counts_are_not_live_regions_and_one_status_region_announces_the_vote(): void
    {
        $event = Event::factory()->create(['voting_mode' => VotingMode::Open]);
        $award = Award::factory()->for($event)->create();
        AwardNominee::factory()->count(3)->for($award)->create();

        $xpath = $this->xpath($this->get(route('awards.show', $event))->assertOk()->getContent());

        $this->assertSame(3, $xpath->query('//*[@data-count]')->length);
        $this->assertSame(0, $xpath->query('//*[@data-count]/ancestor-or-self::*[@aria-live]')->length);

        $liveRegions = $xpath->query('//*[@aria-live]');
        $this->assertSame(1, $liveRegions->length);
        $this->assertSame('polite', $liveRegions->item(0)->getAttribute('aria-live'));
        $this->assertTrue($liveRegions->item(0)->hasAttribute('data-vote-announcer'));
        $this->assertStringContainsString('sr-only', $liveRegions->item(0)->getAttribute('class'));
    }

    public function test_the_csp_allows_cloudflare_turnstile(): void
    {
        $event = Event::factory()->create();

        $policy = (string) $this->get(route('awards.show', $event))->headers->get('Content-Security-Policy-Report-Only');

        $this->assertStringContainsString('challenges.cloudflare.com', $policy);
        $this->assertMatchesRegularExpression('/script-src [^;]*https:\/\/challenges\.cloudflare\.com/', $policy);
        $this->assertMatchesRegularExpression('/frame-src [^;]*https:\/\/challenges\.cloudflare\.com/', $policy);
    }

    /**
     * The awards page's own section, without the shared landing nav and footer around it.
     */
    private function awardsSection(string $html): string
    {
        $this->assertMatchesRegularExpression('/<section[^>]*data-award-voting.*?<\/section>/s', $html);
        preg_match('/<section[^>]*data-award-voting.*?<\/section>/s', $html, $matches);

        return $matches[0];
    }

    /**
     * @return array<string, mixed>
     */
    private function stringsBlob(string $html): array
    {
        $this->assertMatchesRegularExpression('/<script type="application\/json" data-award-voting-strings>(.*?)<\/script>/s', $html);
        preg_match('/<script type="application\/json" data-award-voting-strings>(.*?)<\/script>/s', $html, $matches);

        return json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }
}
