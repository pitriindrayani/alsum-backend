<?php

namespace App\Http\Middleware;

use App\Kis\HttpStatusCodes;
use App\Kis\PermissionCheck;
use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        if($request->auth->level == 'superadmin'){
            return $next($request);
        }
        if ($request->auth->level !== 'developer') {
            $check = PermissionCheck::checkByMenu($permission, $request->auth->id, $request->auth->id_role);
            if (!$check) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_UNAUTHORIZED,
                    'error' => true,
                    'message' => 'Permission denied'
                ], HttpStatusCodes::HTTP_UNAUTHORIZED);
            }
        }
        return $next($request);
    }
}
