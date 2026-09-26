<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_every_staff_member_with_their_role(): void
    {
        $admin = User::factory()->create(['name' => 'Main Admin']);
        User::factory()->checkIn()->create(['name' => 'Door Staff']);

        $this->actingAs($admin)->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee('Main Admin')
            ->assertSee('Door Staff');
    }

    public function test_admin_can_add_a_check_in_staff_member(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => 'Door Staff',
            'email' => 'door@example.com',
            'role' => 'check_in',
            'password' => 'a-long-password',
            'password_confirmation' => 'a-long-password',
        ])->assertRedirect(route('admin.staff.index'));

        $staff = User::where('email', 'door@example.com')->firstOrFail();
        $this->assertSame(UserRole::CheckIn, $staff->role);
        $this->assertTrue(Hash::check('a-long-password', $staff->password));
    }

    public function test_new_staff_need_a_unique_email_and_a_confirmed_password_of_eight_characters(): void
    {
        $admin = User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => 'Door Staff',
            'email' => 'taken@example.com',
            'role' => 'superuser',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['email', 'role', 'password']);
    }

    public function test_editing_keeps_the_password_when_left_blank(): void
    {
        $admin = User::factory()->create();
        $staff = User::factory()->checkIn()->create();
        $originalHash = $staff->password;

        $this->actingAs($admin)->put(route('admin.staff.update', $staff), [
            'name' => 'Renamed',
            'email' => $staff->email,
            'role' => 'check_in',
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
            'role' => 'admin',
            'password' => 'another-long-password',
            'password_confirmation' => 'another-long-password',
        ])->assertRedirect(route('admin.staff.index'));

        $this->assertSame(UserRole::Admin, $staff->fresh()->role);
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

        $this->actingAs($admin)->put(route('admin.staff.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'check_in',
        ])->assertSessionHasErrors('role');

        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }

    public function test_an_admin_can_be_demoted_while_another_admin_remains(): void
    {
        $admin = User::factory()->create();
        $otherAdmin = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.staff.update', $otherAdmin), [
            'name' => $otherAdmin->name,
            'email' => $otherAdmin->email,
            'role' => 'check_in',
        ])->assertRedirect(route('admin.staff.index'));

        $this->assertSame(UserRole::CheckIn, $otherAdmin->fresh()->role);
    }
}
