<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\Sponsor;
use App\Models\SponsorTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SponsorTest extends TestCase
{
    use RefreshDatabase;

    public function test_sponsor_belongs_to_event(): void
    {
        $event = Event::factory()->create();
        $sponsor = Sponsor::factory()->for($event)->create();

        $this->assertTrue($sponsor->event->is($event));
    }

    public function test_event_has_many_sponsors(): void
    {
        $event = Event::factory()->create();
        $tier = SponsorTier::factory()->for($event)->create(['name_en' => 'Gold']);
        Sponsor::factory()->count(2)->for($event)->create(['sponsor_tier_id' => $tier->id]);

        $this->assertCount(2, $event->sponsors);
        $this->assertTrue($event->sponsors->first()->tier->is($tier));
    }

    public function test_sponsor_belongs_to_a_tier(): void
    {
        $event = Event::factory()->create();
        $tier = SponsorTier::factory()->for($event)->create();
        $sponsor = Sponsor::factory()->for($event)->create(['sponsor_tier_id' => $tier->id]);

        $this->assertTrue($sponsor->tier->is($tier));
    }
}
