<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Permission;
use Illuminate\Support\Str;

/**
 * Which permission opens which admin route.
 *
 * Deny by default: a route that no permission lists is open to the built-in Admin role only.
 * ADMIN_ONLY names the routes that are Admin-only on purpose, so the route-coverage test can tell
 * a decision from an oversight.
 */
final class AdminPermissions
{
    /** @var list<string> */
    public const ADMIN_ONLY = ['admin.staff.*', 'admin.roles.*'];

    public static function for(string $routeName): ?Permission
    {
        foreach (Permission::cases() as $permission) {
            if (Str::is($permission->routes(), $routeName)) {
                return $permission;
            }
        }

        return null;
    }

    public static function isAdminOnly(string $routeName): bool
    {
        return Str::is(self::ADMIN_ONLY, $routeName);
    }
}
