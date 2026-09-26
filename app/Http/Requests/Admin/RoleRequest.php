<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    /**
     * The built-in Admin role is locked: refused before its input is even validated.
     */
    public function authorize(): bool
    {
        $role = $this->route('role');

        return ! ($role instanceof Role && $role->is_system);
    }

    public function rules(): array
    {
        /** @var Role|null $role */
        $role = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:60', Rule::unique('roles', 'name')->ignore($role?->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::enum(Permission::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('Role name'),
            'permissions.*' => __('Permission'),
        ];
    }

    /**
     * @return array{name: string, permissions: list<string>}
     */
    public function roleAttributes(): array
    {
        return [
            'name' => $this->validated('name'),
            'permissions' => array_values(array_unique($this->validated('permissions') ?? [])),
        ];
    }
}
