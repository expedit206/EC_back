<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class AuthToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization');

        // return response()->json(['token' => $token]);
        if (!$token) {
            return response()->json(['error' => 'Token manquant'], 401);
        }

        $user = User::where('token', $token)->first();
        if (!$user) {
            return response()->json(['error' => 'Token invalide'], 401);
        }

        if ($user->token_expires_at && Carbon::now()->greaterThan($user->token_expires_at)) {
            $user->update(['token' => null, 'token_expires_at' => null]);
            return response()->json(['error' => 'Token expiré'], 401);
        }

        $request->merge(['user' => $user]);
        return $next($request);
    }
}