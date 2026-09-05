<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\SiteContent;
use App\Models\User;
use App\Support\SiteContentRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisualContentEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_section_carries_an_anchor_key(): void
    {
        foreach (SiteContentRegistry::sections() as $key => $definition) {
            $this->assertArrayHasKey('anchor', $definition, "Section [{$key}] has no anchor key.");
        }
    }

    public function test_the_editor_shows_the_real_page_beside_the_form(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.site-content.edit', 'hero').'?lang=en');

        $response->assertOk();
        $response->assertSee('<iframe', false);
        // Pointed at the live page, scrolled to the section being edited.
        $response->assertSee(route('home').'#hero', false);
    }

    public function test_a_section_without_a_block_of_its_own_previews_the_top_of_the_page(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.site-content.edit', 'branding').'?lang=en');

        $response->assertOk();
        $response->assertSee('<iframe', false);
        $response->assertDontSee(route('home').'#', false);
    }

    public function test_the_editor_lists_every_section_for_jumping_between_them(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.site-content.edit', 'hero').'?lang=en');

        foreach (SiteContentRegistry::sections() as $key => $definition) {
            $response->assertSee(route('admin.site-content.edit', $key), false);
        }
    }

    public function test_the_form_guards_against_leaving_with_unsaved_changes(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.site-content.edit', 'hero').'?lang=en')
            ->assertSee('unsavedGuard', false);
    }

    public function test_the_index_shows_an_uploaded_image_as_a_thumbnail(): void
    {
        SiteContent::create([
            'section' => 'about',
            'field_key' => 'image',
            'value_en' => 'https://example.test/about.jpg',
            'value_ar' => null,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.site-content.index').'?lang=en')
            ->assertOk()
            ->assertSee('https://example.test/about.jpg', false);
    }

    public function test_the_index_says_which_sections_have_been_edited(): void
    {
        SiteContent::create([
            'section' => 'hero',
            'field_key' => 'headline',
            'value_en' => 'A headline somebody wrote',
            'value_ar' => null,
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.site-content.index').'?lang=en');

        $response->assertSee('1 of 5 edited');
        $response->assertSee('Using the wording it ships with');
    }

    public function test_the_screens_stay_closed_to_visitors_who_are_not_signed_in(): void
    {
        $this->get(route('admin.site-content.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.site-content.edit', 'hero'))->assertRedirect(route('admin.login'));
    }
}
