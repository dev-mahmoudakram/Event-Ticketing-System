<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Staff roles and the permissions each one grants. The built-in Admin role is shown but locked.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::withCount('users')->orderByDesc('is_system')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.form', ['role' => new Role(['permissions' => []])]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        Role::create($request->roleAttributes());

        return redirect()->route('admin.roles.index')->with('success', __('Role created.'));
    }

    public function edit(Role $role): View
    {
        abort_if($role->is_system, 403);

        return view('admin.roles.form', ['role' => $role]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->is_system, 403);

        $role->update($request->roleAttributes());

        return redirect()->route('admin.roles.index')->with('success', __('Role updated.'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->is_system, 403);

        if ($role->users()->exists()) {
            return redirect()->route('admin.roles.index')
                ->with('error', __('Move this role\'s staff to another role before deleting it.'));
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', __('Role deleted.'));
    }
}
