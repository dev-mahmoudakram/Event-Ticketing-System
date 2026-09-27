<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    public function test_the_roles_page_lists_every_role_with_its_staff_count(): void
    {
        User::factory()->count(2)->checkIn()->create();

        $this->actingAs($this->admin)->get(route('admin.roles.index', ['lang' => 'en']))
            ->assertOk()
            ->assertSeeInOrder(['Admin', 'Project Manager', 'Registration Desk', 'Sales', 'Speakers', 'Sponsors'])
            ->assertSee(route('admin.roles.edit', Role::where('name', 'Sales')->sole()), false)
            ->assertDontSee(route('admin.roles.edit', Role::system()), false);
    }

    public function test_the_form_shows_the_permission_matrix(): void
    {
        $this->actingAs($this->admin)->get(route('admin.roles.create', ['lang' => 'en']))
            ->assertOk()
            ->assertSee('value="ticket_requests"', false)
            ->assertSee('value="registration_desk"', false)
            ->assertSee('Tickets and sales');
    }

    public function test_an_admin_creates_a_role_with_permissions(): void
    {
        $this->actingAs($this->admin)->post(route('admin.roles.store'), [
            'name' => 'Media team',
            'permissions' => ['landing_page', 'agenda_workshops'],
        ])->assertRedirect(route('admin.roles.index'));

        $role = Role::where('name', 'Media team')->sole();
        $this->assertTrue($role->grants(Permission::LandingPage));
        $this->assertTrue($role->grants(Permission::AgendaWorkshops));
        $this->assertFalse($role->grants(Permission::TicketRequests));
    }

    public function test_a_role_can_be_saved_with_no_permissions(): void
    {
        $this->actingAs($this->admin)->post(route('admin.roles.store'), ['name' => 'Waiting'])
            ->assertRedirect(route('admin.roles.index'));

        $this->assertSame([], Role::where('name', 'Waiting')->sole()->permissions);
    }

    public function test_an_admin_edits_a_role(): void
    {
        $sales = Role::where('name', 'Sales')->sole();

        $this->actingAs($this->admin)->put(route('admin.roles.update', $sales), [
            'name' => 'Sales team',
            'permissions' => ['ticket_requests', 'inbox'],
        ])->assertRedirect(route('admin.roles.index'));

        $sales->refresh();
        $this->assertSame('Sales team', $sales->name);
        $this->assertEqualsCanonicalizing(['ticket_requests', 'inbox'], $sales->permissions);
    }

    public function test_unticking_everything_survives_a_failed_save(): void
    {
        $sales = Role::where('name', 'Sales')->sole();

        $this->actingAs($this->admin)
            ->from(route('admin.roles.edit', $sales))
            ->put(route('admin.roles.update', $sales), ['name' => ''])
            ->assertSessionHasErrors('name');

        // The form comes back with nothing ticked, as the admin left it — not the saved ticks.
        $this->actingAs($this->admin)->get(route('admin.roles.edit', $sales))
            ->assertOk()
            ->assertDontSee('value="ticket_requests" checked', false);

        $this->assertContains('ticket_requests', $sales->fresh()->permissions);
    }

    public function test_the_access_column_reads_as_sections_in_arabic(): void
    {
        $this->actingAs($this->admin)->get(route('admin.roles.index', ['lang' => 'ar']))
            ->assertOk()
            ->assertSee('<th>الأقسام</th>', false)
            ->assertDontSee('<th>الدخول</th>', false);
    }

    public function test_names_are_required_and_unique(): void
    {
        $this->actingAs($this->admin)->post(route('admin.roles.store'), ['name' => 'Sales'])
            ->assertSessionHasErrors('name');
        $this->actingAs($this->admin)->post(route('admin.roles.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_unknown_permissions_are_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('admin.roles.store'), [
            'name' => 'Sneaky',
            'permissions' => ['ticket_requests', 'staff'],
        ])->assertSessionHasErrors('permissions.1');

        $this->assertDatabaseMissing('roles', ['name' => 'Sneaky']);
    }

    public function test_an_unused_role_can_be_deleted(): void
    {
        $role = Role::factory()->create();

        $this->actingAs($this->admin)->delete(route('admin.roles.destroy', $role))
            ->assertRedirect(route('admin.roles.index'));

        $this->assertModelMissing($role);
    }

    public function test_a_role_with_staff_cannot_be_deleted(): void
    {
        $desk = User::factory()->checkIn()->create();

        $this->actingAs($this->admin)->delete(route('admin.roles.destroy', $desk->role))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($desk->role);
        $this->assertSame('Registration Desk', $desk->fresh()->role->name);
    }

    public function test_the_admin_role_is_locked(): void
    {
        $system = Role::system();

        $this->actingAs($this->admin)->get(route('admin.roles.edit', $system))->assertForbidden();
        $this->actingAs($this->admin)->put(route('admin.roles.update', $system), ['name' => 'Boss', 'permissions' => []])->assertForbidden();
        $this->actingAs($this->admin)->delete(route('admin.roles.destroy', $system))->assertForbidden();

        $this->assertSame('Admin', $system->fresh()->name);
    }

    public function test_only_the_admin_reaches_the_roles_page(): void
    {
        $everything = User::factory()->withPermissions(...Permission::cases())->create();

        $this->actingAs($everything)->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($everything)->post(route('admin.roles.store'), ['name' => 'Mine', 'permissions' => ['dashboard']])->assertForbidden();
        $this->assertDatabaseMissing('roles', ['name' => 'Mine']);
    }

    public function test_the_admin_sidebar_links_to_roles(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertSee(route('admin.roles.index'), false);
    }

    public function test_a_non_admin_cannot_change_or_delete_a_role(): void
    {
        $everything = User::factory()->withPermissions(...Permission::cases())->create();
        $sales = Role::where('name', 'Sales')->sole();

        $this->actingAs($everything)->put(route('admin.roles.update', $sales), ['name' => 'Mine', 'permissions' => ['dashboard']])->assertForbidden();
        $this->actingAs($everything)->delete(route('admin.roles.destroy', $sales))->assertForbidden();

        $this->assertSame('Sales', $sales->fresh()->name);
    }
}
