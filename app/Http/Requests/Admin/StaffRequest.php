<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
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
            'role' => [
                'required',
                Rule::enum(UserRole::class),
                function (string $attribute, mixed $value, Closure $fail) use ($staff): void {
                    if ($staff === null || $value === UserRole::Admin->value || ! $staff->isAdmin()) {
                        return;
                    }

                    // Taking admin away from yourself would lock you out of this page, and taking
                    // it from the last admin would leave nobody able to manage the site.
                    if ($staff->is($this->user())) {
                        $fail(__('You can\'t remove your own admin access.'));
                    } elseif (User::where('role', UserRole::Admin)->count() <= 1) {
                        $fail(__('At least one admin is required.'));
                    }
                },
            ],
            'password' => [$staff === null ? 'required' : 'nullable', 'confirmed', Password::min(8)],
        ];
    }
}
