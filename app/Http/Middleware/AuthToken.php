<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
   
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json(['error' => 'Token manquant'], 401);
        }

        $user = User::where('token', $token)->first();

        if (!$user) {
            return response()->json(['error' => 'Token invalide'], 401);
        }

        $request->merge(['user' => $user]);
        return $next($request);
    }
}