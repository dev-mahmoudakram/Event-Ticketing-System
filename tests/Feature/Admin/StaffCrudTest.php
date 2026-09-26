<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffCrudTest extends TestCase
{
    use RefreshDatabase;

    private function deskRole(): Role
    {
        return Role::where('name', 'Registration Desk')->sole();
    }

    public function test_admin_sees_every_staff_member_with_their_role(): void
    {
        $admin = User::factory()->create(['name' => 'Main Admin']);
        User::factory()->checkIn()->create(['name' => 'Door Staff']);

        $this->actingAs($admin)->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee('Main Admin')
            ->assertSee('Door Staff')
            ->assertSee('Registration Desk');
    }

    public function test_the_role_picker_lists_every_role(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.staff.create'))
            ->assertOk()
            ->assertSeeInOrder(['Admin', 'Project Manager', 'Registration Desk', 'Sales']);
    }

    public function test_admin_can_add_a_staff_member_with_a_role(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => 'Door Staff',
            'email' => 'door@example.com',
            'role_id' => $this->deskRole()->id,
            'password' => 'a-long-password',
            'password_confirmation' => 'a-long-password',
        ])->assertRedirect(route('admin.staff.index'));

        $staff = User::where('email', 'door@example.com')->firstOrFail();
        $this->assertTrue($staff->role->is($this->deskRole()));
        $this->assertTrue(Hash::check('a-long-password', $staff->password));
    }

    public function test_new_staff_need_a_unique_email_a_real_role_and_a_confirmed_password_of_eight_characters(): void
    {
        $admin = User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => 'Door Staff',
            'email' => 'taken@example.com',
            'role_id' => 99999,
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['email', 'role_id', 'password']);
    }

    public function test_editing_keeps_the_password_when_left_blank(): void
    {
        $admin = User::factory()->create();
        $staff = User::factory()->checkIn()->create();
        $originalHash = $staff->password;

        $this->actingAs($admin)->put(route('admin.staff.update', $staff), [
            'name' => 'Renamed',
            'email' => $staff->email,
            'role_id' => $staff->role_id,
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.staff.index'));

        $this->assertSame('Renamed', $staff->fresh()->name);
        $this->assertSame($originalHash, $staff->fresh()->password);
    }

    public function test_editing_can_change_the_password_and_role(): void
    {
        $admin = User::factory()->create();
        $staff = User::factory()->checkIn()->create();

        $this->actingAs($admin)->put(route('admin.staff.update', $staff), [
            'name' => $staff->name,
            'email' => $staff->email,
            'role_id' => Role::system()->id,
            'password' => 'another-long-password',
            'password_confirmation' => 'another-long-password',
        ])->assertRedirect(route('admin.staff.index'));

        $this->assertTrue($staff->fresh()->isAdmin());
        $this->assertTrue(Hash::check('another-long-password', $staff->fresh()->password));
    }

    public function test_admin_can_remove_a_staff_member(): void
    {
        $admin = User::factory()->create();
        $staff = User::factory()->checkIn()->create();

        $this->actingAs($admin)->delete(route('admin.staff.destroy', $staff))
            ->assertRedirect(route('admin.staff.index'));

        $this->assertModelMissing($staff);
    }

    public function test_an_admin_cannot_remove_themselves(): void
    {
        $admin = User::factory()->create();
        User::factory()->create();

        $this->actingAs($admin)->delete(route('admin.staff.destroy', $admin))
            ->assertSessionHas('error');

        $this->assertModelExists($admin);
    }

    public function test_the_last_admin_cannot_be_demoted(): void
    {
        $admin = User::factory()->create();
        User::factory()->checkIn()->create();

        $this->actingAs($admin)->put(route('admin.staff.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role_id' => $this->deskRole()->id,
        ])->assertSessionHasErrors('role_id');

        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_an_admin_can_be_demoted_while_another_admin_remains(): void
    {
        $admin = User::factory()->create();
        $otherAdmin = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.staff.update', $otherAdmin), [
            'name' => $otherAdmin->name,
            'email' => $otherAdmin->email,
            'role_id' => $this->deskRole()->id,
        ])->assertRedirect(route('admin.staff.index'));

        $this->assertFalse($otherAdmin->fresh()->isAdmin());
    }
}
