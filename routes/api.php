<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CommercantController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BoutiqueController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\CollaborationController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\LitigeController;
use App\Http\Controllers\AbonnementController;
use App\Http\Controllers\ParrainageController;


Route::post('register', [UserController::class, 'register']);
Route::middleware('guest')->group(function () {
Route::post('login', [UserController::class, 'login']);
});
// Routes protégées
    // Authentification
    // Route::post('register', [UserController::class, 'register']);


    Route::post('register', [UserController::class, 'register']);
    Route::post('login', [UserController::class, 'login']);


Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    // Routes protégées
    Route::get('produits', [ProduitController::class, 'index']);
    Route::middleware('')->group(function () {
        Route::get('user/{user}', [UserController::class, 'profile']);
        Route::post('logout', [UserController::class, 'logout']);
       
        Route::post('commercants', [CommercantController::class, 'store']);
        Route::get('commercants', [CommercantController::class, 'index']);
        Route::post('boutiques', [BoutiqueController::class, 'store']);
        Route::get('boutiques', [BoutiqueController::class, 'index']);
        Route::post('produits', [ProduitController::class, 'store']);
        Route::post('collaborations', [CollaborationController::class, 'store']);
        Route::patch('collaborations/{id}', [CollaborationController::class, 'update']);
        Route::post('commandes', [CommandeController::class, 'store']);
        Route::patch('commandes/{id}/status', [CommandeController::class, 'updateStatus']);
        Route::post('litiges', [LitigeController::class, 'store']);
        Route::patch('litiges/{id}', [LitigeController::class, 'update']);
        // Route::post('abonnements', [AbonnementController::class, 'store']);
        Route::post('parrainages', [ParrainageController::class, 'store']);
    });