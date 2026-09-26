<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Permission;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A staff role: a name and the permissions it grants. The built-in Admin role (is_system) is
 * locked and grants everything regardless of its stored list.
 */
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    protected $fillable = ['name', 'permissions', 'is_system'];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
        ];
    }

    public static function system(): self
    {
        return self::query()->where('is_system', true)->firstOrFail();
    }

    public function grants(Permission $permission): bool
    {
        return $this->is_system || in_array($permission->value, $this->permissions ?? [], true);
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
