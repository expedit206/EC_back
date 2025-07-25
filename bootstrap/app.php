<?php

use App\Http\Middleware\AuthToken;

use Illuminate\Foundation\Application;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
    api: __DIR__ . '/../routes/api.php',    // <-- ajoute cette ligne
    apiPrefix: 'api/v1',

    commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
    //
    $middleware->api(append: [
        HandleCors::class, // CORS
        // 'throttle:api', // Limitation des requêtes
        SubstituteBindings::class, // Liaison des modèles
    ]);
    $middleware->web(append: [
        EncryptCookies::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        VerifyCsrfToken::class,
    ]);

    $middleware->alias([
        'auth.token' => AuthToken::class,
    ]);
    // Middleware pour les requêtes API "stateful" (SPA)
    
})
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withProviders([
        \App\Providers\AppServiceProvider::class, // Assurez-vous que ce provider est listé
    ])
    ->withSchedule(function ($schedule): void {
        // $schedule->command('product:counts')->hourly(); // Exécute la commande toutes les heures
    })
  
        ->create();