<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_faq(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.faqs.store', $event), [
            'question_ar' => 'كيف أدفع؟', 'question_en' => 'How do I pay?',
            'answer_ar' => 'بعد الموافقة', 'answer_en' => 'After approval', 'sort_order' => 0,
        ]);

        $response->assertRedirect(route('admin.events.faqs.index', $event));
        $this->assertDatabaseHas('faqs', ['event_id' => $event->id, 'question_en' => 'How do I pay?']);
    }

    /**
     * The answer is rendered on the public FAQ page with {!! !!} (SanitizedRichText cast on
     * Faq), because it may legitimately contain its own formatting. This proves the real HTTP
     * write path — not just the isolated App\Support\RichText unit — actually strips a script
     * tag before it ever reaches the database.
     */
    public function test_the_faq_answer_is_sanitized_on_save(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($admin)->post(route('admin.events.faqs.store', $event), [
            'question_ar' => 'س', 'question_en' => 'Q',
            'answer_ar' => 'ج', 'answer_en' => '<p>Safe</p><script>alert(1)</script>',
            'sort_order' => 0,
        ]);

        $this->assertDatabaseHas('faqs', ['event_id' => $event->id, 'answer_en' => '<p>Safe</p>']);
    }

    public function test_creating_a_faq_requires_bilingual_answer(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.events.faqs.store', $event), [
            'question_ar' => 'كيف أدفع؟', 'question_en' => 'How do I pay?',
            'answer_ar' => '', 'answer_en' => '',
        ]);

        $response->assertSessionHasErrors(['answer_ar', 'answer_en']);
    }

    public function test_admin_can_delete_a_faq(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $faq = Faq::factory()->for($event)->create();

        $response = $this->actingAs($admin)->delete(route('admin.events.faqs.destroy', [$event, $faq]));

        $response->assertRedirect(route('admin.events.faqs.index', $event));
        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }

    public function test_admin_can_view_the_index_page_with_records(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        Faq::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.faqs.index', $event));

        $response->assertOk();
    }

    public function test_admin_can_view_the_edit_page(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();
        $faq = Faq::factory()->for($event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.faqs.edit', [$event, $faq]));

        $response->assertOk();
    }
}
