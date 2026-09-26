<?php

declare(strict_types=1);

namespace App\Http\Middleware;

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

        $allowed = $user !== null && ($permission !== null ? $user->hasPermission($permission) : $user->isAdmin());

        abort_unless($allowed, 403);

        return $next($request);
    }
}
