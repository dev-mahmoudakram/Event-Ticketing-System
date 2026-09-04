<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SiteFaq;
use Database\Seeders\HubFaqSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HubFaqSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_gives_the_faq_section_starter_questions(): void
    {
        $this->seed(HubFaqSeeder::class);

        $this->assertGreaterThan(0, SiteFaq::count());
        $this->get(route('home').'?lang=en')->assertSee('id="faq"', false);
    }

    public function test_running_it_again_does_not_duplicate_or_overwrite_questions(): void
    {
        $this->seed(HubFaqSeeder::class);
        SiteFaq::query()->delete();
        SiteFaq::create([
            'question_en' => 'Ours', 'question_ar' => 'سؤالنا',
            'answer_en' => 'Our answer', 'answer_ar' => 'إجابتنا', 'sort_order' => 0,
        ]);

        $this->seed(HubFaqSeeder::class);

        $this->assertSame(1, SiteFaq::count());
        $this->assertSame('Ours', SiteFaq::first()->question_en);
    }

    public function test_every_starter_question_is_written_in_both_languages(): void
    {
        $this->seed(HubFaqSeeder::class);

        foreach (SiteFaq::all() as $faq) {
            $this->assertNotEmpty($faq->question_ar);
            $this->assertNotEmpty($faq->answer_ar);
            $this->assertNotEmpty($faq->question_en);
            $this->assertNotEmpty($faq->answer_en);
        }
    }
}
