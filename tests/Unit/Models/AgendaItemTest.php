<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\AgendaItem;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_agenda_item_belongs_to_event(): void
    {
        $event = Event::factory()->create();
        $item = AgendaItem::factory()->for($event)->create();

        $this->assertTrue($item->event->is($event));
    }

    public function test_agenda_item_defaults_to_its_events_session_type(): void
    {
        $event = Event::factory()->create();
        $item = AgendaItem::factory()->for($event)->create();

        $this->assertSame('Session', $item->sessionType->name_en);
        $this->assertSame($event->id, $item->sessionType->event_id);
    }
}
