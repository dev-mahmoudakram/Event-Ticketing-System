<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\InvitationStatus;
use App\Models\Event;
use App\Models\Invitation;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_an_unused_unexpired_invitation_is_usable(): void
    {
        $event = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create();
        $invitation = Invitation::factory()->for($event)->for($ticketType)->create();

        $this->assertTrue($invitation->isUsable());

        $invitation->update(['expires_at' => now()->subSecond()]);
        $this->assertFalse($invitation->isUsable());

        $invitation->update(['expires_at' => now()->addDay(), 'status' => InvitationStatus::Used]);
        $this->assertFalse($invitation->isUsable());

        $invitation->update(['status' => InvitationStatus::Revoked]);
        $this->assertFalse($invitation->isUsable());
    }
}
