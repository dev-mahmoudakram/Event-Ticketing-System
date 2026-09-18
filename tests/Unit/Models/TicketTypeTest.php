<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_type_belongs_to_event(): void
    {
        $event = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $this->assertTrue($ticketType->event->is($event));
    }

    public function test_workshop_slot_count_can_be_zero_or_null(): void
    {
        $general = TicketType::factory()->create(['workshop_slot_count' => 0]);
        $platinum = TicketType::factory()->create(['workshop_slot_count' => null]);

        $this->assertSame(0, $general->workshop_slot_count);
        $this->assertNull($platinum->workshop_slot_count);
    }

    public function test_popular_label_falls_back_to_the_default_wording(): void
    {
        $ticketType = TicketType::factory()->create(['popular_label_en' => null]);

        $this->assertSame('Most Popular', $ticketType->popularLabel());
    }

    public function test_popular_label_uses_the_admins_own_wording_when_set(): void
    {
        $ticketType = TicketType::factory()->create(['popular_label_en' => 'Best Value']);

        $this->assertSame('Best Value', $ticketType->popularLabel());
    }

    public function test_popular_label_falls_back_when_set_to_blank_text(): void
    {
        $ticketType = TicketType::factory()->create(['popular_label_en' => '   ']);

        $this->assertSame('Most Popular', $ticketType->popularLabel());
    }
}
