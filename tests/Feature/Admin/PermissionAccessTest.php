<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Permission;
use App\Enums\TicketStatus;
use App\Models\Event;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Support\AdminPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class PermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private function staffWithRole(string $name): User
    {
        return User::factory()->create(['role_id' => Role::where('name', $name)->sole()->id]);
    }

    /**
     * Every admin and desk route must be tied to a permission or be Admin-only on purpose, so a
     * new page can't ship without someone deciding who may open it.
     */
    public function test_every_admin_route_is_mapped_or_admin_only(): void
    {
        $unmapped = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter(fn (?string $name) => $name !== null && Str::startsWith($name, ['admin.', 'check-in.']))
            // "admin." is the login form's POST: unnamed, so it only carries the group prefix.
            ->reject(fn (string $name) => in_array($name, ['admin.', 'admin.login', 'admin.logout', 'admin.no-access'], true))
            ->reject(fn (string $name) => AdminPermissions::for($name) !== null || AdminPermissions::isAdminOnly($name))
            ->values()
            ->all();

        $this->assertSame([], $unmapped, 'Map these routes to a Permission or add them to AdminPermissions::ADMIN_ONLY.');
    }

    public function test_sales_opens_ticket_work_and_nothing_else(): void
    {
        $sales = $this->staffWithRole('Sales');
        $event = Event::factory()->create();

        $this->actingAs($sales)->get(route('admin.events.ticket-requests.index', $event))->assertOk();
        $this->actingAs($sales)->get(route('admin.events.invitations.index', $event))->assertOk();
        $this->actingAs($sales)->get(route('admin.events.discount-coupons.index', $event))->assertOk();
        $this->actingAs($sales)->get(route('admin.events.reports.show', $event))->assertOk();

        $this->actingAs($sales)->get(route('admin.events.speakers.index', $event))->assertForbidden();
        $this->actingAs($sales)->get(route('admin.events.ticket-types.index', $event))->assertForbidden();
        $this->actingAs($sales)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($sales)->get(route('check-in.events'))->assertForbidden();
    }

    public function test_speakers_and_sponsors_roles_stay_in_their_lane(): void
    {
        $event = Event::factory()->create();
        $speakers = $this->staffWithRole('Speakers');
        $sponsors = $this->staffWithRole('Sponsors');

        $this->actingAs($speakers)->get(route('admin.events.speaker-requests.index', $event))->assertOk();
        $this->actingAs($speakers)->get(route('admin.events.speakers.index', $event))->assertOk();
        $this->actingAs($speakers)->get(route('admin.events.sponsor-requests.index', $event))->assertForbidden();

        $this->actingAs($sponsors)->get(route('admin.events.sponsor-requests.index', $event))->assertOk();
        $this->actingAs($sponsors)->get(route('admin.events.sponsor-tiers.index', $event))->assertOk();
        $this->actingAs($sponsors)->get(route('admin.events.ticket-requests.index', $event))->assertForbidden();
    }

    public function test_project_manager_sees_the_three_teams_and_the_dashboard(): void
    {
        $manager = $this->staffWithRole('Project Manager');
        $event = Event::factory()->create();

        $this->actingAs($manager)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($manager)->get(route('admin.events.speaker-requests.index', $event))->assertOk();
        $this->actingAs($manager)->get(route('admin.events.sponsor-requests.index', $event))->assertOk();
        $this->actingAs($manager)->get(route('admin.events.ticket-requests.index', $event))->assertOk();
        $this->actingAs($manager)->get(route('admin.events.content.edit', $event))->assertForbidden();
        $this->actingAs($manager)->get(route('admin.staff.index'))->assertForbidden();
    }

    public function test_a_forbidden_action_changes_nothing(): void
    {
        $speakers = $this->staffWithRole('Speakers');
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->for($event)->create(['status' => TicketStatus::Pending]);

        $this->actingAs($speakers)
            ->patch(route('admin.events.ticket-requests.update-status', [$event, $ticket, 'approved']))
            ->assertForbidden();

        $this->assertSame(TicketStatus::Pending, $ticket->fresh()->status);
    }

    public function test_every_permission_still_does_not_open_staff_or_roles(): void
    {
        $everything = User::factory()->withPermissions(...Permission::cases())->create();
        $other = User::factory()->checkIn()->create();

        $this->actingAs($everything)->get(route('admin.staff.index'))->assertForbidden();
        $this->actingAs($everything)->get(route('admin.staff.edit', $other))->assertForbidden();
        $this->actingAs($everything)->delete(route('admin.staff.destroy', $other))->assertForbidden();
        $this->assertModelExists($other);
    }

    public function test_the_registration_desk_needs_its_permission(): void
    {
        $event = Event::factory()->create();

        $this->actingAs(User::factory()->withPermissions(Permission::TicketRequests)->create())
            ->get(route('check-in.index', $event))->assertForbidden();
        $this->actingAs($this->staffWithRole('Registration Desk'))
            ->get(route('check-in.index', $event))->assertOk();
    }

    public function test_the_admin_opens_everything(): void
    {
        $admin = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($admin)->get(route('admin.staff.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.events.content.edit', $event))->assertOk();
        $this->actingAs($admin)->get(route('check-in.index', $event))->assertOk();
    }
}
