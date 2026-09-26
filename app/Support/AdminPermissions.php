<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\EventStatus;
use App\Enums\Permission;
use App\Models\Event;
use App\Models\User;
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

    /**
     * Where someone lands after logging in: the Dashboard when their role has it, otherwise
     * their first allowed section in Permission order. Sections that belong to an event open for
     * the latest published event (or the latest event of any status); with no events at all they
     * are skipped. A role with nothing to open gets the no-access page.
     */
    public static function homeFor(User $user): string
    {
        $event = null;
        $eventLooked = false;

        foreach (Permission::cases() as $permission) {
            if (! $user->hasPermission($permission)) {
                continue;
            }

            if (! $permission->needsEvent()) {
                return route($permission->entryRoute());
            }

            if (! $eventLooked) {
                $event = Event::query()->where('status', EventStatus::Published)->orderByDesc('start_date')->first()
                    ?? Event::query()->orderByDesc('start_date')->first();
                $eventLooked = true;
            }

            if ($event !== null) {
                return route($permission->entryRoute(), $event);
            }
        }

        return route('admin.no-access');
    }
}
