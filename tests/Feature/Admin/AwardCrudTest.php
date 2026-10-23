<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Permission;
use App\Enums\VotingMode;
use App\Models\Award;
use App\Models\AwardNominee;
use App\Models\AwardVote;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AwardCrudTest extends TestCase
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

    public function test_an_admin_adds_edits_reorders_and_deletes_an_award(): void
    {
        $this->actingAs($this->admin)->post(route('admin.events.awards.store', $this->event), [
            'name_ar' => 'أفضل صانع محتوى', 'name_en' => 'Creator of the Year',
        ])->assertRedirect(route('admin.events.awards.index', $this->event));

        $award = $this->event->awards()->sole();
        $this->assertSame(1, $award->sort_order);

        $this->actingAs($this->admin)->put(route('admin.events.awards.update', [$this->event, $award]), [
            'name_ar' => 'أفضل بودكاست', 'name_en' => 'Best Podcast',
        ])->assertRedirect(route('admin.events.awards.index', $this->event));
        $this->assertSame('Best Podcast', $award->fresh()->name_en);

        $second = Award::factory()->create(['event_id' => $this->event->id, 'sort_order' => 5]);
        $this->actingAs($this->admin)->postJson(route('admin.events.awards.reorder', $this->event), ['ids' => [$second->id, $award->id]])->assertOk();
        $this->assertSame([$second->id, $award->id], $this->event->awards()->pluck('id')->all());

        $this->actingAs($this->admin)->postJson(route('admin.events.awards.reorder', $this->event), ['ids' => [Award::factory()->create()->id]])
            ->assertUnprocessable();

        $this->actingAs($this->admin)->delete(route('admin.events.awards.destroy', [$this->event, $award]))
            ->assertRedirect(route('admin.events.awards.index', $this->event));
        $this->assertModelMissing($award);
    }

    public function test_an_award_needs_both_names(): void
    {
        $this->actingAs($this->admin)->post(route('admin.events.awards.store', $this->event), ['name_en' => 'Only English'])
            ->assertSessionHasErrors('name_ar');
    }

    public function test_the_list_shows_nominee_and_vote_counts(): void
    {
        $award = Award::factory()->create(['event_id' => $this->event->id]);
        $nominees = AwardNominee::factory()->count(2)->create(['award_id' => $award->id]);
        AwardVote::factory()->count(3)->create(['award_id' => $award->id, 'award_nominee_id' => $nominees[0]->id]);
        AwardVote::factory()->removed()->create(['award_id' => $award->id, 'award_nominee_id' => $nominees[1]->id]);

        $this->actingAs($this->admin)->get(route('admin.events.awards.index', ['event' => $this->event, 'lang' => 'en']))
            ->assertOk()
            ->assertSeeInOrder(['data-nominee-count>2<', 'data-vote-count>3<'], false);

        $this->actingAs($this->admin)->get(route('admin.events.awards.nominees.index', ['event' => $this->event, 'award' => $award, 'lang' => 'en']))
            ->assertOk()
            ->assertSee('data-vote-count>3<', false);
    }

    public function test_an_admin_manages_nominees_with_a_photo(): void
    {
        Storage::fake('public');
        $award = Award::factory()->create(['event_id' => $this->event->id]);

        $this->actingAs($this->admin)->post(route('admin.events.awards.nominees.store', [$this->event, $award]), [
            'name_ar' => 'سارة', 'name_en' => 'Sara', 'bio_en' => 'A creator.', 'image' => UploadedFile::fake()->image('n.jpg'),
        ])->assertRedirect(route('admin.events.awards.nominees.index', [$this->event, $award]));

        $nominee = $award->nominees()->sole();
        $this->assertSame(1, $nominee->sort_order);
        $this->assertNotNull($nominee->image_path);
        Storage::disk('public')->assertExists($nominee->image_path);
        $oldPath = $nominee->image_path;

        $this->actingAs($this->admin)->get(route('admin.events.awards.nominees.edit', [$this->event, $award, $nominee]))->assertOk();

        $this->actingAs($this->admin)->put(route('admin.events.awards.nominees.update', [$this->event, $award, $nominee]), [
            'name_ar' => 'سارة', 'name_en' => 'Sara K', 'image' => UploadedFile::fake()->image('m.jpg'),
        ])->assertRedirect(route('admin.events.awards.nominees.index', [$this->event, $award]));

        $nominee->refresh();
        $this->assertSame('Sara K', $nominee->name_en);
        $this->assertNotSame($oldPath, $nominee->image_path);
        Storage::disk('public')->assertMissing($oldPath);

        $other = AwardNominee::factory()->create(['award_id' => $award->id, 'sort_order' => 9]);
        $this->actingAs($this->admin)->postJson(route('admin.events.awards.nominees.reorder', [$this->event, $award]), ['ids' => [$other->id, $nominee->id]])->assertOk();
        $this->assertSame([$other->id, $nominee->id], $award->nominees()->pluck('id')->all());

        $path = $nominee->image_path;
        $this->actingAs($this->admin)->delete(route('admin.events.awards.nominees.destroy', [$this->event, $award, $nominee]))
            ->assertRedirect(route('admin.events.awards.nominees.index', [$this->event, $award]));
        $this->assertModelMissing($nominee);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_another_events_award_is_not_found(): void
    {
        $foreign = Award::factory()->create();

        $this->actingAs($this->admin)->get(route('admin.events.awards.edit', [$this->event, $foreign]))->assertNotFound();
        $this->actingAs($this->admin)->delete(route('admin.events.awards.destroy', [$this->event, $foreign]))->assertNotFound();
        $this->actingAs($this->admin)->get(route('admin.events.awards.nominees.index', [$this->event, $foreign]))->assertNotFound();
        $this->assertModelExists($foreign);
    }

    public function test_another_awards_nominee_is_not_found(): void
    {
        $award = Award::factory()->create(['event_id' => $this->event->id]);
        $foreign = AwardNominee::factory()->create();

        $this->actingAs($this->admin)->get(route('admin.events.awards.nominees.edit', [$this->event, $award, $foreign]))->assertNotFound();
        $this->actingAs($this->admin)->delete(route('admin.events.awards.nominees.destroy', [$this->event, $award, $foreign]))->assertNotFound();
        $this->assertModelExists($foreign);
    }

    public function test_voting_settings_save_and_close_must_follow_open(): void
    {
        $this->actingAs($this->admin)->put(route('admin.events.awards.settings', $this->event), [
            'voting_mode' => 'open', 'voting_opens_at' => '2026-11-01T10:00', 'voting_closes_at' => '2026-11-05T10:00',
        ])->assertRedirect(route('admin.events.awards.index', $this->event))->assertSessionHasNoErrors();

        $this->event->refresh();
        $this->assertSame(VotingMode::Open, $this->event->voting_mode);
        $this->assertSame('2026-11-05 10:00', $this->event->voting_closes_at->format('Y-m-d H:i'));

        $this->actingAs($this->admin)->put(route('admin.events.awards.settings', $this->event), [
            'voting_mode' => 'schedule', 'voting_opens_at' => '2026-11-05T10:00', 'voting_closes_at' => '2026-11-05T10:00',
        ])->assertSessionHasErrors('voting_closes_at');

        $this->actingAs($this->admin)->put(route('admin.events.awards.settings', $this->event), ['voting_mode' => 'bogus'])
            ->assertSessionHasErrors('voting_mode');

        $this->actingAs($this->admin)->put(route('admin.events.awards.settings', $this->event), ['voting_mode' => 'closed'])
            ->assertSessionHasNoErrors();
        $this->assertSame(VotingMode::Closed, $this->event->fresh()->voting_mode);
        $this->assertNull($this->event->fresh()->voting_opens_at);
    }

    public function test_the_awards_permission_opens_the_section(): void
    {
        $this->actingAs(User::factory()->withPermissions(Permission::Awards)->create())
            ->get(route('admin.events.awards.index', $this->event))->assertOk();
        $this->actingAs(User::factory()->withPermissions(Permission::Speakers)->create())
            ->get(route('admin.events.awards.index', $this->event))->assertForbidden();
    }
}
