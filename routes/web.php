<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login']);

// Routes protégées par authentification
Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [UserController::class, 'profile']);
    Route::post('commercants', [CommercantController::class, 'store']); // Créer un compte commerçant
    Route::get('commercants', [CommercantController::class, 'index']);
    Route::post('boutiques', [BoutiqueController::class, 'store']);
    Route::get('boutiques', [BoutiqueController::class, 'index']);
    Route::post('parrainages', [ParrainageController::class, 'store']);
});