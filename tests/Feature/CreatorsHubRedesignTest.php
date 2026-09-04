<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\SiteSection;
use App\Models\Event;
use App\Models\SiteContent;
use App\Models\SiteFaq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatorsHubRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function setContent(SiteSection $section, string $key, string $value): void
    {
        SiteContent::updateOrCreate(
            ['section' => $section, 'field_key' => $key],
            ['value_en' => $value, 'value_ar' => $value],
        );
    }

    public function test_the_audience_switch_renders_both_sides(): void
    {
        $response = $this->get(route('home').'?lang=en');

        $response->assertOk();
        $response->assertSee('id="audiences"', false);
        $response->assertSee('role="tablist"', false);
        $response->assertSee('If you design or build');
        $response->assertSee('If you supply or sponsor');
    }

    public function test_the_stats_band_is_absent_until_it_is_filled_in(): void
    {
        $this->get(route('home'))->assertDontSee('data-stats-band', false);
    }

    public function test_a_stat_needs_both_a_figure_and_a_label_to_show(): void
    {
        // A figure with no label would render a number with nothing explaining it.
        $this->setContent(SiteSection::Stats, 'figure_one', '120');

        $this->get(route('home').'?lang=en')->assertDontSee('120');
    }

    public function test_a_complete_stat_pair_is_shown(): void
    {
        $this->setContent(SiteSection::Stats, 'figure_one', '120');
        $this->setContent(SiteSection::Stats, 'label_one', 'Exhibitors');

        $response = $this->get(route('home').'?lang=en');

        $response->assertSee('120');
        $response->assertSee('Exhibitors');
    }

    public function test_why_egypt_stays_hidden_without_a_heading_and_a_point(): void
    {
        $this->setContent(SiteSection::WhyEgypt, 'heading', 'Why Egypt');

        // Heading alone is not enough — the section would render an empty list.
        $this->get(route('home').'?lang=en')->assertDontSee('id="why-egypt"', false);
    }

    public function test_why_egypt_renders_admin_supplied_points(): void
    {
        $this->setContent(SiteSection::WhyEgypt, 'heading', 'Why Egypt');
        $this->setContent(SiteSection::WhyEgypt, 'point_one', 'A growing construction sector');

        $response = $this->get(route('home').'?lang=en');

        $response->assertSee('id="why-egypt"', false);
        $response->assertSee('A growing construction sector');
    }

    public function test_the_faq_section_appears_only_once_questions_exist(): void
    {
        $this->get(route('home'))->assertDontSee('id="faq"', false);

        SiteFaq::create([
            'question_en' => 'Who can attend?',
            'question_ar' => 'من يمكنه الحضور؟',
            'answer_en' => 'Anyone working in the sector.',
            'answer_ar' => 'أي شخص يعمل في القطاع.',
            'sort_order' => 0,
        ]);

        $response = $this->get(route('home').'?lang=en');
        $response->assertSee('id="faq"', false);
        $response->assertSee('Who can attend?');
    }

    public function test_the_featured_event_shows_countable_metrics_only(): void
    {
        $event = Event::factory()->create([
            'status' => EventStatus::Published,
            'name_en' => 'Design Week',
            'start_date' => '2027-03-01',
            'end_date' => '2027-03-03',
        ]);

        $response = $this->get(route('home').'?lang=en');

        $response->assertSee('Design Week');
        // Three calendar days, derived from the event's own dates.
        $response->assertSee('Days');
        $response->assertSee('href="'.route('landing.show', $event).'"', false);
    }

    public function test_an_admin_can_save_site_content(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.site-content.update'), [
            'content' => [
                'stats' => ['figure_one' => ['en' => '40', 'ar' => '٤٠'], 'label_one' => ['en' => 'Studios', 'ar' => 'استوديو']],
            ],
        ]);

        $response->assertRedirect(route('admin.site-content.edit'));
        $this->assertDatabaseHas('site_contents', ['section' => 'stats', 'field_key' => 'figure_one', 'value_en' => '40']);
    }

    public function test_unknown_content_keys_are_ignored(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.site-content.update'), [
            'content' => [
                'stats' => ['not_a_real_field' => ['en' => 'x', 'ar' => 'x']],
                'not_a_real_section' => ['heading' => ['en' => 'x', 'ar' => 'x']],
            ],
        ]);

        $this->assertDatabaseCount('site_contents', 0);
    }

    public function test_guests_cannot_edit_site_content(): void
    {
        $this->get(route('admin.site-content.edit'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.site-faqs.index'))->assertRedirect(route('admin.login'));
    }
}
