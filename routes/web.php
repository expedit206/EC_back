<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use Illuminate\Broadcasting\BroadcastController;

Route::post('register', [UserController::class, 'register']);
Route::post('api/v1/login', [UserController::class, 'login']);  

// Route::post('/broadcasting/auth', [BroadcastController::class, 'authenticate']);