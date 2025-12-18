<?php

namespace App\Http\Middleware;

use App\Kis\HttpStatusCodes;
use Closure;
use Exception;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use App\Kis\PermissionAssign;
use Firebase\JWT\ExpiredException;

class SdcJwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $permission = null)
    {
        $token = $request->header('Authorization');
        if (!$token) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_UNAUTHORIZED,
                'error'   => true,
                'message' => "Unauthorized: Token not provided"
            ], HttpStatusCodes::HTTP_UNAUTHORIZED);
        }

        try {
            $credentials = JWT::decode($token, new Key(env('JWT_SECRET_KEY'), env('JWT_ALGORITMA')));
            $user = User::where('email', $credentials->email)->where('state', true)->first();
            if ($user) {
                $request->auth = $user;
                return $next($request);
            }
        } catch (ExpiredException $e) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_UNAUTHORIZED,
                'error'   => true,
                'message' => 'Unauthorized: Provided token is expired.'
            ], HttpStatusCodes::HTTP_UNAUTHORIZED);
        } catch (Exception $e) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_UNAUTHORIZED,
                'error'   => true,
                'message' => $e->getMessage()
            ], HttpStatusCodes::HTTP_UNAUTHORIZED);
        }

        return response()->json([
            'status' => HttpStatusCodes::HTTP_UNAUTHORIZED,
            'error'   => true,
            'message' => 'Forbidden'
        ], HttpStatusCodes::HTTP_UNAUTHORIZED);
    }
}
