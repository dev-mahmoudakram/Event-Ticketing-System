<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\TicketStatus;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Services\TicketQrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketCheckInTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->event = Event::factory()->create(['status' => 'published']);
    }

    private function ticket(array $attributes = []): Ticket
    {
        static $n = 0;
        $n++;

        return Ticket::create(array_merge([
            'event_id' => $this->event->id,
            'ticket_type_id' => TicketType::factory()->for($this->event)->create()->id,
            'name' => 'Kareem Al-Sayed',
            'email' => 'kareem'.$n.'@example.com',
            'phone' => '+2010012345'.$n,
            'ticket_number' => 'CCS2026-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'ticket_id' => Str::random(40),
            'status' => TicketStatus::TicketIssued,
            'is_paid' => true,
        ], $attributes));
    }

    public function test_the_desk_is_closed_to_visitors_who_are_not_signed_in(): void
    {
        $ticket = $this->ticket();

        $this->get(route('check-in.index', $this->event))->assertRedirect(route('admin.login'));
        $this->post(route('check-in.scan', $this->event), ['qr_code' => $ticket->ticket_id])
            ->assertRedirect(route('admin.login'));
        $this->post(route('check-in.store', $this->event), ['qr_code' => $ticket->ticket_id])
            ->assertRedirect(route('admin.login'));

        $this->assertNull($ticket->fresh()->checked_in_at);
    }

    public function test_a_signed_in_admin_admits_an_attendee(): void
    {
        $ticket = $this->ticket();

        $this->actingAs(User::factory()->create())
            ->postJson(route('check-in.scan', $this->event), ['qr_code' => $ticket->ticket_id])
            ->assertOk()
            ->assertJsonPath('result', 'verified')
            ->assertJsonPath('ticket.name', 'Kareem Al-Sayed');

        $ticket->refresh();
        $this->assertNotNull($ticket->checked_in_at);
        $this->assertSame(TicketStatus::CheckedIn, $ticket->status);
    }

    public function test_a_ticket_cannot_be_used_twice(): void
    {
        $ticket = $this->ticket();
        $admin = User::factory()->create();

        $this->actingAs($admin)->postJson(route('check-in.scan', $this->event), ['qr_code' => $ticket->ticket_id]);
        $firstArrival = $ticket->fresh()->checked_in_at;

        $this->actingAs($admin)
            ->postJson(route('check-in.scan', $this->event), ['qr_code' => $ticket->ticket_id])
            ->assertOk()
            ->assertJsonPath('result', 'used');

        // The second scan must not move the arrival time either.
        $this->assertEquals($firstArrival, $ticket->fresh()->checked_in_at);
    }

    public function test_an_unpaid_ticket_is_turned_away(): void
    {
        $ticket = $this->ticket(['is_paid' => false, 'status' => TicketStatus::Approved]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('check-in.scan', $this->event), ['qr_code' => $ticket->ticket_id])
            ->assertJsonPath('result', 'unpaid');

        $this->assertNull($ticket->fresh()->checked_in_at);
    }

    public function test_a_ticket_from_another_event_is_refused(): void
    {
        $ticket = $this->ticket();
        $other = Event::factory()->create();

        $this->actingAs(User::factory()->create())
            ->postJson(route('check-in.scan', $other), ['qr_code' => $ticket->ticket_id])
            ->assertJsonPath('result', 'invalid');

        $this->assertNull($ticket->fresh()->checked_in_at);
    }

    public function test_nonsense_is_refused_without_touching_any_ticket(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('check-in.scan', $this->event), ['qr_code' => 'not-a-ticket'])
            ->assertJsonPath('result', 'invalid')
            ->assertJsonPath('ticket', null);
    }

    public function test_the_manual_box_admits_the_same_way_the_camera_does(): void
    {
        $ticket = $this->ticket();

        $this->actingAs(User::factory()->create())
            ->post(route('check-in.store', $this->event), ['qr_code' => $ticket->ticket_id])
            ->assertRedirect(route('check-in.index', $this->event))
            ->assertSessionHas('success');

        $this->assertNotNull($ticket->fresh()->checked_in_at);
    }

    public function test_the_manual_box_refuses_a_used_ticket(): void
    {
        $ticket = $this->ticket(['status' => TicketStatus::CheckedIn, 'checked_in_at' => now()]);

        $this->actingAs(User::factory()->create())
            ->post(route('check-in.store', $this->event), ['qr_code' => $ticket->ticket_id])
            ->assertSessionHas('error');
    }

    public function test_the_qr_code_carries_no_link_a_phone_camera_could_open(): void
    {
        $ticket = $this->ticket();

        $payload = (new TicketQrCode)->payloadFor($ticket);

        $this->assertSame($ticket->ticket_id, $payload);
        $this->assertStringNotContainsString('http', (string) $payload);
        $this->assertStringNotContainsString('/', (string) $payload);
    }

    public function test_a_ticket_link_from_an_older_email_still_scans(): void
    {
        $ticket = $this->ticket();

        // Tickets issued before the code became a bare id carried a URL ending in the id.
        $this->actingAs(User::factory()->create())
            ->postJson(route('check-in.scan', $this->event), [
                'qr_code' => 'https://example.test/check-in/'.$this->event->id.'/'.$ticket->ticket_id,
            ])
            ->assertJsonPath('result', 'verified');
    }
}
