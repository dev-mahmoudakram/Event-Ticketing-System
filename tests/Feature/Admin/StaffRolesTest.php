<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\EventStatus;
use App\Enums\TicketStatus;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_in_staff_are_refused_every_admin_page(): void
    {
        $staff = User::factory()->checkIn()->create();
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->for($event)->create(['status' => TicketStatus::Pending]);

        $this->actingAs($staff)->get(route('admin.dashboard'))->assertRedirect(route('check-in.events'));
        $this->actingAs($staff)->get(route('admin.events.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.events.reports.export', $event))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.staff.index'))->assertForbidden();
        $this->actingAs($staff)->patch(route('admin.events.ticket-requests.update-status', [$event, $ticket, 'approved']))->assertForbidden();

        $this->assertSame(TicketStatus::Pending, $ticket->fresh()->status);
    }

    public function test_check_in_staff_can_use_the_registration_desk(): void
    {
        $staff = User::factory()->checkIn()->create();
        $event = Event::factory()->create(['status' => EventStatus::Published]);

        $this->actingAs($staff)->get(route('check-in.events'))->assertOk()->assertSee(route('check-in.index', $event), false);
        $this->actingAs($staff)->get(route('check-in.index', $event))->assertOk();
        $this->actingAs($staff)->postJson(route('check-in.scan', $event), ['qr_code' => str_repeat('x', 40)])->assertOk();
    }

    public function test_check_in_staff_do_not_see_admin_links(): void
    {
        $staff = User::factory()->checkIn()->create();
        $event = Event::factory()->create();

        $this->actingAs($staff)->get(route('check-in.index', $event))
            ->assertOk()
            ->assertDontSee('href="'.route('admin.dashboard').'"', false)
            ->assertDontSee(route('admin.events.index'), false)
            ->assertDontSee(route('admin.staff.index'), false)
            ->assertSee(route('admin.logout'), false);
    }

    public function test_admins_still_see_everything(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee(route('admin.staff.index'), false);
        $this->actingAs($admin)->get(route('check-in.index', $event))->assertOk();
    }

    public function test_login_sends_each_role_to_its_home(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);
        User::factory()->checkIn()->create(['email' => 'door@example.com']);

        $this->post(route('admin.login'), ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->post(route('admin.logout'));

        $this->post(route('admin.login'), ['email' => 'door@example.com', 'password' => 'password'])
            ->assertRedirect(route('check-in.events'));
    }

    public function test_guests_are_still_sent_to_login(): void
    {
        $this->get(route('check-in.events'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.staff.index'))->assertRedirect(route('admin.login'));
    }
}
