<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RequiredPage;
use App\Models\Event;
use App\Models\EventPage;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPagesModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_event_gets_the_six_required_pages(): void
    {
        $event = Event::factory()->create();

        $this->assertSame(
            ['terms', 'privacy-policy', 'refund-policy', 'delivery-policy', 'about', 'contact'],
            $event->pages()->pluck('slug')->all(),
        );
        $this->assertTrue($event->pages()->get()->every(fn (EventPage $page) => $page->isRequired() && $page->is_published));
    }

    public function test_seeding_required_pages_twice_creates_them_once(): void
    {
        $event = Event::factory()->create();

        EventPage::seedRequiredFor($event);

        $this->assertSame(6, $event->pages()->count());
    }

    public function test_drafts_are_filled_from_the_event(): void
    {
        $event = Event::factory()->create([
            'name_en' => 'Content Creators Summit', 'name_ar' => 'قمة صناع المحتوى',
            'contact_email' => 'hello@ccs.test', 'contact_phone' => '+20 100 000 0000',
        ]);
        TicketType::factory()->for($event)->create(['currency' => 'EGP']);
        $event->pages()->delete();
        EventPage::seedRequiredFor($event->fresh());

        $refund = $event->pages()->where('key', RequiredPage::Refund->value)->sole();
        $this->assertStringContainsString('Content Creators Summit', $refund->body_en);
        $this->assertStringContainsString('hello@ccs.test', $refund->body_en);
        $this->assertStringContainsString('EGP', $refund->body_en);
        $this->assertStringContainsString('قمة صناع المحتوى', $refund->body_ar);
        $this->assertTrue($refund->needsDetails());
    }

    public function test_a_page_without_placeholders_does_not_need_details(): void
    {
        $page = EventPage::factory()->create(['body_en' => '<p>Done.</p>', 'body_ar' => '<p>تم.</p>']);

        $this->assertFalse($page->needsDetails());
    }

    public function test_a_placeholder_in_either_language_flags_the_page(): void
    {
        $page = EventPage::factory()->create(['body_en' => '<p>Done.</p>', 'body_ar' => '<p>[اسم الشركة القانوني]</p>']);

        $this->assertTrue($page->needsDetails());
    }

    public function test_required_pages_know_their_place(): void
    {
        $this->assertSame('refund-policy', RequiredPage::Refund->slug());
        $this->assertSame('Refund & Cancellation Policy', RequiredPage::Refund->titleEn());
        $this->assertSame(6, count(RequiredPage::cases()));
    }

    public function test_the_arabic_drafts_keep_the_phone_number_left_to_right(): void
    {
        $event = Event::factory()->create(['contact_phone' => '+20 100 000 0000']);
        $isolated = "\u{2066}+20 100 000 0000\u{2069}";

        foreach (['terms', 'refund'] as $key) {
            $page = $event->pages()->where('key', $key)->sole();
            $this->assertStringContainsString($isolated, $page->body_ar, $key);
            $this->assertStringNotContainsString("\u{2066}", $page->body_en, $key);
        }
    }
}
