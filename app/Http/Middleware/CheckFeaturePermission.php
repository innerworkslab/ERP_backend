<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeaturePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permissionKey)
    {

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        if (!$user->hasPermission($permissionKey)) {
            return response()->json([
                'message' => 'Forbidden: permission denied'
            ], 403);
        }
        // super admin bypass
        // if ($user->role && $user->role->is_super) {
        //     return $next($request);
        // }

        // $roleHasFeature = $user->role
        //     ->features()
        //     ->where('name', $feature)
        //     ->exists();

        // $userHasFeature = $user
        //     ->features()
        //     ->where('name', $feature)
        //     ->exists();

        // if (!$roleHasFeature && !$userHasFeature) {

        //     return response()->json([
        //         'message' => 'Forbidden: permission denied'
        //     ], 403);
        // }

        return $next($request);
    }
}
