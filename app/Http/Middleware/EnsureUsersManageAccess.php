<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUsersManageAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            throw new AuthorizationException('ACCESS DENIED.');
        }

        $adminRoles = config('tyro-dashboard.admin_roles', ['admin', 'super-admin']);

        foreach ($adminRoles as $role) {
            if (method_exists($user, 'hasRole') && $user->hasRole($role)) {
                return $next($request);
            }
        }

        if (method_exists($user, 'hasPrivilege') && $user->hasPrivilege('users.manage')) {
            return $next($request);
        }

        throw new AuthorizationException('ACCESS DENIED.');
    }
}
