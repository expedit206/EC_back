<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot()
    {

        // Broadcast::routes();

        // Broadcast::auth(function ($request) {
        //     return $request->user(); // l'user est résolu par ton guard (API par ex.)
        // });
        Broadcast::routes(); // Utilisez les middlewares appropriés
        // Broadcast::routes(['middleware' => ['broadcast.token']]); // Utilisez les middlewares appropriés
        require base_path('routes/channels.php');
    }
}