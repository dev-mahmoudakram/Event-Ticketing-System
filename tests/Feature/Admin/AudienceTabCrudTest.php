<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AudienceCard;
use App\Models\AudienceTab;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AudienceTabCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_the_screens_are_closed_to_visitors_who_are_not_signed_in(): void
    {
        $tab = AudienceTab::factory()->create();

        $this->get(route('admin.audience-tabs.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.audience-tabs.store'), ['label_en' => 'Sneaked in'])->assertRedirect(route('admin.login'));
        $this->delete(route('admin.audience-tabs.destroy', $tab))->assertRedirect(route('admin.login'));

        $this->assertDatabaseCount('audience_tabs', 1);
    }

    public function test_an_admin_creates_a_tab(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.audience-tabs.store'), [
                'label_en' => 'If you teach',
                'label_ar' => 'إذا كنت تدرّس',
                'lede_en' => 'For educators.',
                'cta_en' => 'Get in touch',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audience_tabs', ['label_en' => 'If you teach', 'lede_en' => 'For educators.']);
    }

    public function test_a_tab_needs_a_label(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.audience-tabs.store'), ['label_en' => ''])
            ->assertSessionHasErrors('label_en');

        $this->assertDatabaseCount('audience_tabs', 0);
    }

    public function test_deleting_a_tab_takes_its_cards_with_it(): void
    {
        $tab = AudienceTab::factory()->create();
        AudienceCard::factory()->count(3)->for($tab, 'tab')->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.audience-tabs.destroy', $tab))
            ->assertRedirect(route('admin.audience-tabs.index'));

        $this->assertDatabaseCount('audience_tabs', 0);
        $this->assertDatabaseCount('audience_cards', 0);
    }

    public function test_a_tab_holds_as_many_cards_as_it_is_given(): void
    {
        $tab = AudienceTab::factory()->create();

        // The section was once capped at four slots per side; nothing caps it now.
        AudienceCard::factory()->count(9)->for($tab, 'tab')->create();

        $this->assertSame(9, $tab->fresh()->cards()->count());
    }

    public function test_a_new_card_joins_the_end_rather_than_the_front(): void
    {
        $tab = AudienceTab::factory()->create();
        AudienceCard::factory()->for($tab, 'tab')->create(['sort_order' => 0]);
        AudienceCard::factory()->for($tab, 'tab')->create(['sort_order' => 1]);

        $this->actingAs($this->admin())
            ->post(route('admin.audience-tabs.cards.store', $tab), ['title_en' => 'Third'])
            ->assertRedirect();

        $this->assertSame('Third', $tab->fresh()->cards->last()->title_en);
    }

    public function test_cards_are_reordered_by_dragging(): void
    {
        $tab = AudienceTab::factory()->create();
        $first = AudienceCard::factory()->for($tab, 'tab')->create(['sort_order' => 0]);
        $second = AudienceCard::factory()->for($tab, 'tab')->create(['sort_order' => 1]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.audience-tabs.cards.reorder', $tab), ['ids' => [$second->id, $first->id]])
            ->assertOk();

        $this->assertSame([$second->id, $first->id], $tab->fresh()->cards->pluck('id')->all());
    }

    public function test_reordering_cannot_reach_a_card_in_another_tab(): void
    {
        $tab = AudienceTab::factory()->create();
        $other = AudienceTab::factory()->create();
        $foreign = AudienceCard::factory()->for($other, 'tab')->create(['sort_order' => 7]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.audience-tabs.cards.reorder', $tab), ['ids' => [$foreign->id]])
            ->assertOk();

        $this->assertSame(7, $foreign->fresh()->sort_order);
    }

    public function test_tabs_are_reordered_by_dragging(): void
    {
        $first = AudienceTab::factory()->create(['sort_order' => 0]);
        $second = AudienceTab::factory()->create(['sort_order' => 1]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.audience-tabs.reorder'), ['ids' => [$second->id, $first->id]])
            ->assertOk();

        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame(1, $first->fresh()->sort_order);
    }

    public function test_a_card_cannot_be_edited_through_the_wrong_tab(): void
    {
        $tab = AudienceTab::factory()->create();
        $other = AudienceTab::factory()->create();
        $card = AudienceCard::factory()->for($other, 'tab')->create();

        $this->actingAs($this->admin())
            ->get(route('admin.audience-tabs.cards.edit', [$tab, $card]))
            ->assertNotFound();
    }
}
