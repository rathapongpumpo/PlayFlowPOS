<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        $role = (string) ($request->user()->role ?? '');
        if (!in_array($role, ['super_admin', 'shop_owner', 'branch_manager', 'cashier'], true)) {
            abort(403, 'Admin only');
        }

        return $next($request);
    }
}
