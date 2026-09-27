<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var User|null $staff */
        $staff = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($staff?->id)],
            'role_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('roles', 'id'),
                function (string $attribute, mixed $value, Closure $fail) use ($staff): void {
                    $newRole = Role::find($value);

                    if ($staff === null || $newRole === null || $newRole->is_system || ! $staff->isAdmin()) {
                        return;
                    }

                    // Taking admin away from yourself would lock you out of this page, and taking
                    // it from the last admin would leave nobody able to manage the site.
                    if ($staff->is($this->user())) {
                        $fail(__('You can\'t remove your own admin access.'));
                    } elseif (User::admins()->count() <= 1) {
                        $fail(__('At least one admin is required.'));
                    }
                },
            ],
            'password' => [$staff === null ? 'required' : 'nullable', 'confirmed', Password::min(8)],
        ];
    }

    public function attributes(): array
    {
        return ['role_id' => __('Role')];
    }
}
