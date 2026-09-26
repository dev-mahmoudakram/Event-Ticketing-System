<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\Speaker;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkshopTest extends TestCase
{
    use RefreshDatabase;

    public function test_workshop_belongs_to_event(): void
    {
        $event = Event::factory()->create();
        $workshop = Workshop::factory()->for($event)->create();

        $this->assertTrue($workshop->event->is($event));
    }

    public function test_workshop_can_have_several_speakers(): void
    {
        $event = Event::factory()->create();
        $speakers = Speaker::factory()->for($event)->count(2)->create();
        $workshop = Workshop::factory()->for($event)->create();

        $workshop->syncSpeakersInOrder($speakers->pluck('id')->all());

        $this->assertCount(2, $workshop->fresh()->speakers);
    }

    public function test_workshop_uses_slug_as_route_key(): void
    {
        $workshop = Workshop::factory()->create(['slug' => 'ai-content-workshop']);

        $this->assertSame('slug', $workshop->getRouteKeyName());
    }

    public function test_a_workshop_knows_whether_it_is_scheduled(): void
    {
        $this->assertTrue(Workshop::factory()->create()->isScheduled());
        $this->assertFalse(Workshop::factory()->unscheduled()->create()->isScheduled());
    }
}
