<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'permissions' => [],
            'is_system' => false,
        ];
    }

    public function withPermissions(Permission ...$permissions): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => array_map(fn (Permission $permission) => $permission->value, $permissions),
        ]);
    }
}
