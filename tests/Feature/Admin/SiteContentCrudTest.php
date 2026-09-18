<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\SiteContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteContentCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_plain_field_is_stored_as_submitted(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.site-content.update', 'hero'), [
            'fields' => ['headline' => ['en' => 'Plain heading <b>bold</b>']],
        ]);

        // "headline" is not declared richtext — sanitization does not run on it at all, and
        // it reaches the database exactly as submitted.
        $this->assertDatabaseHas('site_contents', [
            'section' => 'hero', 'field_key' => 'headline', 'value_en' => 'Plain heading <b>bold</b>',
        ]);
    }

    /**
     * "hero.body" is declared 'type' => 'richtext' in SiteContentRegistry and rendered on the
     * public page with @siteRichText (unescaped). This proves the real HTTP write path strips
     * a script tag before it is ever stored, not just the isolated RichText helper.
     */
    public function test_a_richtext_field_is_sanitized_on_save(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.site-content.update', 'hero'), [
            'fields' => [
                'headline' => ['en' => 'Headline'],
                'body' => ['en' => '<p>Safe</p><script>alert(1)</script>'],
            ],
        ]);

        $this->assertDatabaseHas('site_contents', [
            'section' => 'hero', 'field_key' => 'body', 'value_en' => '<p>Safe</p>',
        ]);
    }

    public function test_a_richtext_field_strips_an_onclick_handler(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.site-content.update', 'about'), [
            'fields' => ['body' => ['en' => '<p onclick="alert(1)">text</p>']],
        ]);

        $this->assertDatabaseHas('site_contents', [
            'section' => 'about', 'field_key' => 'body', 'value_en' => '<p>text</p>',
        ]);
    }

    public function test_resubmitting_a_richtext_field_updates_it(): void
    {
        $admin = User::factory()->create();
        SiteContent::create([
            'section' => 'hero', 'field_key' => 'body', 'value_ar' => null, 'value_en' => '<p>Old</p>',
        ]);

        $this->actingAs($admin)->put(route('admin.site-content.update', 'hero'), [
            'fields' => ['body' => ['en' => '<p>New</p>']],
        ]);

        $this->assertDatabaseHas('site_contents', [
            'section' => 'hero', 'field_key' => 'body', 'value_en' => '<p>New</p>',
        ]);
        $this->assertSame(1, SiteContent::where('section', 'hero')->where('field_key', 'body')->count());
    }

    public function test_the_screens_are_closed_to_visitors_who_are_not_signed_in(): void
    {
        $this->get(route('admin.site-content.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.site-content.edit', 'hero'))->assertRedirect(route('admin.login'));
        $this->put(route('admin.site-content.update', 'hero'), [])->assertRedirect(route('admin.login'));
    }

    public function test_an_unknown_section_is_not_found(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.site-content.edit', 'not-a-real-section'))->assertNotFound();
    }
}
