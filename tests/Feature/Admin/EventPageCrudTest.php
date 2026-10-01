<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Permission;
use App\Models\Event;
use App\Models\EventPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPageCrudTest extends TestCase
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

    public function test_the_list_shows_required_pages_and_flags_drafts(): void
    {
        $this->actingAs($this->admin)->get(route('admin.events.pages.index', ['event' => $this->event, 'lang' => 'en']))
            ->assertOk()
            ->assertSee('Refund &amp; Cancellation Policy', false)
            ->assertSee('Needs your details')
            ->assertSee('Required');
    }

    public function test_an_admin_adds_edits_and_deletes_a_custom_page(): void
    {
        $this->actingAs($this->admin)->post(route('admin.events.pages.store', $this->event), [
            'title_ar' => 'قواعد السلوك', 'title_en' => 'Code of Conduct', 'slug' => 'code-of-conduct',
            'body_en' => '<p>Be kind.</p>', 'body_ar' => '<p>كن لطيفًا.</p>', 'show_in_footer' => '1', 'is_published' => '1',
        ])->assertRedirect(route('admin.events.pages.index', $this->event));

        $page = $this->event->pages()->where('slug', 'code-of-conduct')->sole();
        $this->assertFalse($page->isRequired());
        $this->assertSame(6, $page->sort_order);

        $this->actingAs($this->admin)->put(route('admin.events.pages.update', [$this->event, $page]), [
            'title_ar' => 'قواعد السلوك', 'title_en' => 'Conduct', 'slug' => 'conduct', 'body_en' => '<p>x</p>',
        ])->assertRedirect(route('admin.events.pages.index', $this->event));
        $this->assertSame('conduct', $page->fresh()->slug);
        $this->assertFalse($page->fresh()->is_published);

        $this->actingAs($this->admin)->delete(route('admin.events.pages.destroy', [$this->event, $page]))
            ->assertRedirect(route('admin.events.pages.index', $this->event));
        $this->assertModelMissing($page);
    }

    public function test_slugs_are_url_safe_and_unique_per_event(): void
    {
        foreach (['Code Of Conduct', 'terms', 'a--b'] as $slug) {
            $this->actingAs($this->admin)->post(route('admin.events.pages.store', $this->event), [
                'title_ar' => 'ص', 'title_en' => 'Page', 'slug' => $slug,
            ])->assertSessionHasErrors('slug');
        }

        EventPage::factory()->create(['slug' => 'faq-extra']);
        $this->actingAs($this->admin)->post(route('admin.events.pages.store', $this->event), [
            'title_ar' => 'ص', 'title_en' => 'Page', 'slug' => 'faq-extra',
        ])->assertSessionDoesntHaveErrors('slug');
    }

    public function test_a_required_page_cannot_be_deleted(): void
    {
        $terms = $this->event->pages()->where('key', 'terms')->sole();

        $this->actingAs($this->admin)->delete(route('admin.events.pages.destroy', [$this->event, $terms]))->assertForbidden();

        $this->assertModelExists($terms);
    }

    public function test_a_required_page_stays_published_at_its_address(): void
    {
        $refund = $this->event->pages()->where('key', 'refund')->sole();

        $this->actingAs($this->admin)->put(route('admin.events.pages.update', [$this->event, $refund]), [
            'title_ar' => 'الاسترداد', 'title_en' => 'Refunds', 'slug' => 'money-back', 'body_en' => '<p>Ours.</p>',
        ])->assertRedirect(route('admin.events.pages.index', $this->event));

        $refund->refresh();
        $this->assertSame('refund-policy', $refund->slug);
        $this->assertTrue($refund->is_published);
        $this->assertSame('Refunds', $refund->title_en);
        $this->assertFalse($refund->show_in_footer);
    }

    public function test_another_events_page_is_not_found(): void
    {
        $foreign = EventPage::factory()->create();

        $this->actingAs($this->admin)->get(route('admin.events.pages.edit', [$this->event, $foreign]))->assertNotFound();
    }

    public function test_pages_reorder(): void
    {
        $ids = $this->event->pages()->pluck('id')->reverse()->values()->all();

        $this->actingAs($this->admin)->postJson(route('admin.events.pages.reorder', $this->event), ['ids' => $ids])->assertOk();

        $this->assertSame($ids, $this->event->pages()->pluck('id')->all());
    }

    public function test_the_pages_permission_opens_the_section(): void
    {
        $editor = User::factory()->withPermissions(Permission::Pages)->create();

        $this->actingAs($editor)->get(route('admin.events.pages.index', $this->event))->assertOk();
        $this->actingAs(User::factory()->withPermissions(Permission::Speakers)->create())
            ->get(route('admin.events.pages.index', $this->event))->assertForbidden();
    }
}
