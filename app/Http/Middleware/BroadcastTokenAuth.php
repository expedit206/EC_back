<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

class BroadcastTokenAuth
{
    public function handle(Request $request, Closure $next)
    {


        // Log::info('Broadcast auth request', [
        //     'middle'=>'aa',
        //     'USER' => $request->user(),
        //     'all_request' => $request->all(),
        //     'headers' => $request->headers->all(),
        //     'cookies' => $request->cookies->all(),
        //     'user_authenticated' => Auth::check(),
        //     'user_id' => Auth::id(),
        //     // 'broad' => Broadcast::auth(
        //     // $request),
        // ]);

        // $authHeader = $request->header('Authorization');

        // // return response('Unauthorized', 401);
        // // if (!$authHeader) {
        // // }

        // // On suppose que le token est stocké en clair
        // $user = $request->user();
        // Auth::login($user); // Authentifie manuellement l'utilisateur

        // if (!$user) {
        //     return response('Unauthorized', 401);
        // }


        return $next($request);
    }
}