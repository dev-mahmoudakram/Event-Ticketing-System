<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SiteContent;
use App\Models\SiteFaq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HubFaqIllustrationTest extends TestCase
{
    use RefreshDatabase;

    private function faq(): void
    {
        SiteFaq::create([
            'question_en' => 'How do I get a ticket?', 'question_ar' => 'كيف أحصل على تذكرة؟',
            'answer_en' => 'Request one.', 'answer_ar' => 'اطلبها.', 'sort_order' => 0,
        ]);
    }

    public function test_the_questions_are_drawn_beside_an_illustration(): void
    {
        $this->faq();

        $response = $this->get(route('home').'?lang=en');

        // Decorative, so it is announced to nobody and read by nobody.
        $response->assertSee('role="presentation"', false);
        $response->assertSee('aria-hidden="true"', false);
    }

    public function test_an_uploaded_image_replaces_the_illustration(): void
    {
        $this->faq();
        SiteContent::create([
            'section' => 'faq',
            'field_key' => 'image',
            'value_en' => '/storage/site/faq.png',
            'value_ar' => '/storage/site/faq.png',
        ]);

        $response = $this->get(route('home').'?lang=en');

        $response->assertSee('/storage/site/faq.png', false);
        $response->assertDontSee('role="presentation"', false);
    }

    public function test_nothing_is_drawn_when_there_are_no_questions(): void
    {
        $this->get(route('home').'?lang=en')->assertDontSee('id="faq"', false);
    }
}
