<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        return view('admin.staff.index', ['staff' => User::orderBy('role')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.staff.form', ['user' => new User(['role' => UserRole::CheckIn])]);
    }

    public function store(StaffRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()->route('admin.staff.index')->with('success', __('Staff member added.'));
    }

    public function edit(User $user): View
    {
        return view('admin.staff.form', ['user' => $user]);
    }

    public function update(StaffRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Left blank on edit means "keep the current password".
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.staff.index')->with('success', __('Staff member updated.'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return redirect()->route('admin.staff.index')->with('error', __('You can\'t remove your own account.'));
        }

        if ($user->isAdmin() && User::where('role', UserRole::Admin)->count() <= 1) {
            return redirect()->route('admin.staff.index')->with('error', __('At least one admin is required.'));
        }

        $user->delete();

        return redirect()->route('admin.staff.index')->with('success', __('Staff member removed.'));
    }
}
