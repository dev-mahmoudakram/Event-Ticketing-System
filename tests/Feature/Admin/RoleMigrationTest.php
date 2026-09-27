<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoleMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_starting_roles_exist_with_their_permissions(): void
    {
        $this->assertTrue(Role::system()->is_system);
        $this->assertSame('Admin', Role::system()->name);

        $roles = Role::where('is_system', false)->pluck('permissions', 'name');

        $this->assertEqualsCanonicalizing(['Speakers', 'Sponsors', 'Sales', 'Project Manager', 'Registration Desk'], $roles->keys()->all());
        $this->assertEqualsCanonicalizing(['speakers', 'speaker_requests'], $roles['Speakers']);
        $this->assertEqualsCanonicalizing(['sponsors', 'sponsor_requests'], $roles['Sponsors']);
        $this->assertEqualsCanonicalizing(['ticket_requests', 'invitations', 'discount_coupons', 'event_reports'], $roles['Sales']);
        $this->assertEqualsCanonicalizing(
            ['dashboard', 'speakers', 'speaker_requests', 'sponsors', 'sponsor_requests', 'ticket_requests', 'invitations', 'discount_coupons', 'event_reports'],
            $roles['Project Manager'],
        );
        $this->assertEqualsCanonicalizing(['registration_desk'], $roles['Registration Desk']);
    }

    /**
     * Rolls just this migration back, recreates staff with the old role column, and migrates
     * again. If a later migration comes to depend on the roles table, this rollback will need
     * revisiting.
     */
    public function test_existing_staff_move_to_the_matching_roles(): void
    {
        $migration = collect(glob(database_path('migrations/*_create_roles_table.php')))->sole();
        $this->artisan('migrate:rollback', ['--path' => $migration, '--realpath' => true])->assertSuccessful();

        $now = now();
        DB::table('users')->insert([
            ['name' => 'Boss', 'email' => 'boss@example.com', 'password' => 'x', 'role' => 'admin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Door', 'email' => 'door@example.com', 'password' => 'x', 'role' => 'check_in', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Odd', 'email' => 'odd@example.com', 'password' => 'x', 'role' => 'mystery', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->artisan('migrate', ['--path' => $migration, '--realpath' => true])->assertSuccessful();

        $this->assertTrue(User::where('email', 'boss@example.com')->sole()->isAdmin());

        foreach (['door@example.com', 'odd@example.com'] as $email) {
            $user = User::where('email', $email)->sole();
            $this->assertSame('Registration Desk', $user->role->name);
            $this->assertTrue($user->hasPermission(Permission::RegistrationDesk));
            $this->assertFalse($user->hasPermission(Permission::TicketRequests));
        }
    }

    public function test_the_admin_role_grants_everything(): void
    {
        $admin = User::factory()->create();

        foreach (Permission::cases() as $permission) {
            $this->assertTrue($admin->hasPermission($permission), $permission->value);
        }
        $this->assertTrue($admin->isAdmin());
    }

    public function test_a_custom_role_grants_only_its_permissions(): void
    {
        $sales = User::factory()->withPermissions(Permission::TicketRequests, Permission::Invitations)->create();

        $this->assertTrue($sales->hasPermission(Permission::TicketRequests));
        $this->assertFalse($sales->hasPermission(Permission::Speakers));
        $this->assertFalse($sales->isAdmin());
    }

    public function test_the_admin_flag_cannot_be_mass_assigned(): void
    {
        $role = Role::create(['name' => 'Sneaky', 'permissions' => [], 'is_system' => true]);

        $this->assertFalse($role->fresh()->is_system);
    }
}
