<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventPage;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPagesPublicTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function event(array $attributes = []): Event
    {
        return Event::factory()->create($attributes + ['status' => EventStatus::Published, 'contact_email' => 'hello@ccs.test', 'contact_phone' => '+20 100 000 0000']);
    }

    public function test_a_page_shows_in_the_readers_language_with_its_last_update(): void
    {
        $event = $this->event();
        $terms = $event->pages()->where('key', 'terms')->sole();
        $terms->update(['body_en' => '<p>English terms.</p>', 'body_ar' => '<p>شروط عربية.</p>']);

        $this->get(route('event-pages.show', [$event, 'terms']).'?lang=en')
            ->assertOk()->assertSee('Terms & Conditions')->assertSee('English terms.')->assertSee('Last updated');
        $this->get(route('event-pages.show', [$event, 'terms']).'?lang=ar')
            ->assertOk()->assertSee('الشروط والأحكام')->assertSee('شروط عربية.');
    }

    public function test_unknown_and_unpublished_pages_are_not_found(): void
    {
        $event = $this->event();
        EventPage::factory()->for($event)->create(['slug' => 'hidden', 'is_published' => false]);

        $this->get(route('event-pages.show', [$event, 'nope']))->assertNotFound();
        $this->get(route('event-pages.show', [$event, 'hidden']))->assertNotFound();
        $this->get(route('event-pages.show', [$this->event(['status' => 'draft']), 'terms']))->assertNotFound();
    }

    public function test_the_contact_page_shows_the_details_and_the_form(): void
    {
        $event = $this->event();

        $this->get(route('event-pages.show', [$event, 'contact']).'?lang=en')
            ->assertOk()
            ->assertSee('mailto:hello@ccs.test', false)
            ->assertSee('tel:+201000000000', false)
            ->assertSee('action="'.route('contact.store', $event).'"', false)
            ->assertSee('name="return_to" value="contact-page"', false);
    }

    public function test_the_contact_page_form_returns_to_the_contact_page(): void
    {
        $event = $this->event();

        $this->post(route('contact.store', $event), [
            'name' => 'Sara', 'email' => 'sara@example.com', 'message' => 'Hello', 'return_to' => 'contact-page',
        ])->assertRedirect(route('event-pages.show', [$event, 'contact']))->assertSessionHas('contact_success');

        $this->post(route('contact.store', $event), [
            'name' => 'Sara', 'email' => 'sara@example.com', 'message' => 'Hello again',
        ])->assertRedirect(route('landing.show', $event).'#contact');
    }

    public function test_the_footer_lists_published_footer_pages_in_order(): void
    {
        $event = $this->event();
        EventPage::factory()->for($event)->create(['title_en' => 'Code of Conduct', 'slug' => 'conduct', 'sort_order' => 99]);
        EventPage::factory()->for($event)->create(['title_en' => 'Secret', 'slug' => 'secret', 'is_published' => false]);
        EventPage::factory()->for($event)->create(['title_en' => 'Not in footer', 'slug' => 'quiet', 'show_in_footer' => false]);

        $this->get(route('landing.show', $event).'?lang=en')
            ->assertSeeInOrder(['Terms & Conditions', 'Privacy Policy', 'Refund & Cancellation Policy', 'Code of Conduct'])
            ->assertSee(route('event-pages.show', [$event, 'refund-policy']), false)
            ->assertDontSee('Secret')
            ->assertDontSee('Not in footer');
    }

    public function test_policies_show_even_when_the_newsletter_is_off(): void
    {
        $event = $this->event();
        $event->update(['visible_sections' => array_merge($event->visible_sections ?? [], ['newsletter' => false])]);

        $this->get(route('landing.show', $event))
            ->assertSee(route('event-pages.show', [$event, 'terms']), false)
            ->assertDontSee('id="newsletter-email"', false);
    }

    public function test_card_logos_show_only_when_tickets_are_sold(): void
    {
        $free = $this->event();
        $this->get(route('landing.show', $free))->assertDontSee('data-payment-logos', false);

        $paid = $this->event();
        TicketType::factory()->for($paid)->create(['price' => 700, 'is_active' => true]);
        $this->get(route('landing.show', $paid))->assertSee('data-payment-logos', false);
    }
}
