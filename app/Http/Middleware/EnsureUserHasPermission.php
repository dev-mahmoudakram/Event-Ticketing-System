<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Support\AdminPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets a staff member through only to pages their role allows. A route no permission lists is
 * treated as Admin-only, so a page added later is closed until someone decides otherwise.
 */
class EnsureUserHasPermission
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = (string) $request->route()?->getName();
        $permission = AdminPermissions::for($routeName);

        // /admin is where everyone lands by habit or bookmark; without the Dashboard, send the
        // person to their own start page rather than a dead-end 403.
        if ($permission === Permission::Dashboard && $user !== null && ! $user->hasPermission(Permission::Dashboard)) {
            return redirect()->to(AdminPermissions::homeFor($user));
        }

        $allowed = $user !== null && ($permission !== null ? $user->hasPermission($permission) : $user->isAdmin());

        abort_unless($allowed, 403);

        return $next($request);
    }
}
