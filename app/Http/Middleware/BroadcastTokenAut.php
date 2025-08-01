<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class BroadcastTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        return response('Unauthorized', 401);
        if (!$authHeader) {
        }

        // On suppose que le token est stocké en clair
        $user = $request->user;

        if (!$user) {
            return response('Unauthorized', 401);
        }

        auth()->login($user); // Authentifie manuellement l'utilisateur

        return $next($request);
    }
}