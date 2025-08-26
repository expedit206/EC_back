<?php

namespace Illuminate\Broadcasting;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class BroadcastControlle extends Controller
{
    /**
     * Authenticate the request for channel access.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function authenticate(Request $request)
    {
        if ($request->hasSession()) {
            $request->session()->reflash();
        }

        // return Broadcast::auth($request);

        Log::info('Broadcast auth request', [
            'USER' => $request->user(),
            'all_request' => $request->all(),
            'headers' => $request->headers->all(),
            'cookies' => $request->cookies->all(),
            'session_id' => $request->session()->getId(),
            'user_authenticated' => Auth::check(),
            'user_id' => Auth::id(),
        ]);
        return true;
    }

    /**
     * Authenticate the current user.
     *
     * See: https://pusher.com/docs/channels/server_api/authenticating-users/#user-authentication.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|null
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function authenticateUser(Request $request)
    {
        if ($request->hasSession()) {
            $request->session()->reflash();
        }

        return Broadcast::resolveAuthenticatedUser($request)
            ?? throw new AccessDeniedHttpException;
    }
}