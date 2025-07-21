<?php

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LitigeController;
use App\Http\Controllers\PanierController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\BoutiqueController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\AbonnementController;
use App\Http\Controllers\CommercantController;
use App\Http\Controllers\ParrainageController;
use App\Http\Controllers\CollaborationController;

Route::get('/redis-test', function () {
    Redis::set('test_key', 'Hello Redis!');
    return Redis::get('test_key'); // Doit retourner "Hello Redis!"
});
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
    // Route::get('produits', [ProduitController::class, 'index']);
    Route::middleware('auth.token')->group(function () {
        Route::get('produits', [ProduitController::class, 'index']);
        Route::get('user', [UserController::class, 'profile']);
        Route::post('logout', [UserController::class, 'logout']);
        
        
        Route::post('produits', [ProduitController::class, 'store']);


        Route::post('collaborations', [CollaborationController::class, 'store']);
        Route::patch('collaborations/{id}', [CollaborationController::class, 'update']);
    Route::get('/collaborations', [CollaborationController::class, 'index'])->name('collaborations.index');
        
        Route::get('commandes', [CommandeController::class, 'index']);
        Route::post('commandes', [CommandeController::class, 'store']);
        Route::patch('commandes/{id}/status', [CommandeController::class, 'updateStatus']);
        
        Route::post('litiges', [LitigeController::class, 'store']);
        Route::patch('litiges/{id}', [LitigeController::class, 'update']);
        // Route::post('abonnements', [AbonnementController::class, 'store']);
        Route::post('parrainages', [ParrainageController::class, 'store']);

    Route::get('/commercant/produits', [CommercantController::class, 'produits'])->name('commercant.produits');
    Route::post('/commercant/produits', [CommercantController::class, 'storeProduit'])->name('commercant.produits.store');
    Route::delete('/commercant/produits/{produit}', [CommercantController::class, 'destroyProduit'])->name('commercant.produits.destroy');
    Route::get('/commercant/profil', [CommercantController::class, 'profil'])->name('commercant.profil');
    Route::put('/commercant/profil', [CommercantController::class, 'updateProfil'])->name('commercant.profil.update');
    Route::put('/commercant/produits/{id}', [CommercantController::class, 'updateProduit'])->name('commercant.produits.update');
    Route::get('/commercant/{commercant}', [CommercantController::class, 'show'])->name('commercant.show');

    Route::put('/user/notifications', [UserController::class, 'updateNotifications'])->name('user.notifications.update');   

    
    Route::post('/panier', [PanierController::class, 'store'])->name('panier.store');
    
    Route::get('/panier', [PanierController::class, 'index'])->name('panier.index');
    Route::put('/panier/{id}', [PanierController::class, 'update'])->name('panier.update');
    Route::delete('/panier/{id}', [PanierController::class, 'destroy'])->name('panier.destroy');

    
    Route::get('/user/badges', [UserController::class, 'badges'])->name('user.badges');


    Route::post('/produits/{id}/favorite', [ProduitController::class, 'toggleFavorite']);
    Route::get('/produits/{produit}', [ProduitController::class, 'show'])->name('produits.show');


    Route::post('/parrainages/generateCode', [ParrainageController::class, 'generateCodeSuggestion']);
    Route::post('/parrainages/createCode', [ParrainageController::class, 'createCode']);
    // ... autres routes
        Route::post('/parrainages/register', [ParrainageController::class, 'registerParrainage']);
    Route::post('/parrainages/validate/{userId}', [ParrainageController::class, 'validateParrainage']);
    Route::get('/parrainages/dashboard', [ParrainageController::class, 'getParrainageDashboard']);
});