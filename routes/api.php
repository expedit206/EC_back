<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\JetonController;
use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AbonnementController;
use App\Http\Controllers\CommercantController;

Route::get('/redis-test', function () {
    Redis::set('test_key', 'Hello Redis!');
    return Redis::get('test_key'); // Doit retourner "Hello Redis!"
});
// Route::post('register', [UserController::class, 'register']);
// Route::middleware('guest')->group(function () {
    // });
    // Routes protégées
    // Authentification
    // Route::post('register', [UserController::class, 'register']);
use App\Http\Controllers\ParrainageController;
use App\Http\Controllers\SubscriptionController;

use Illuminate\Broadcasting\BroadcastController;
use App\Http\Controllers\CollaborationController;

Route::post('/login', [UserController::class, 'login']);
// Route::post('register', [UserController::class, 'register']);

// Route::post('/broadcasting/aut', function () {
//     return \Auth::user();
// });

Route::get('/', function () {
    return view('welcome'); // Laravel va chercher resources/views/welcome.blade.php
});
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
// Route::get('/produits/related/{produit}', [CategoryController::class, 'relatedProduct'])->name('categories.index');
    // Routes protégées
    // Route::get('produits', [ProduitController::class, 'index']);
    Route::middleware('auth:sanctum')->group(function () {




        
    Route::get('/conversations', [ChatController::class, 'conversations']);

    Route::get('user', [UserController::class, 'profile']);
        Route::get('produits', [ProduitController::class, 'index']);

    Route::post('/profile/photo', [ProfileController::class, 'updateProfilePhoto']); // Nouvelle route
    Route::post('logout', [UserController::class, 'logout']);


    // Route::post('produits', [ProduitController::class, 'store'])->middleware('premium:product');


        Route::post('collaborations', [CollaborationController::class, 'store']);
        Route::patch('collaborations/{id}', [CollaborationController::class, 'update']);
    Route::get('/collaborations', [CollaborationController::class, 'index'])->name('collaborations.index');
        
        // Route::post('abonnements', [AbonnementController::class, 'store']);
        Route::post('parrainages', [ParrainageController::class, 'store']);
        
        Route::post('/commercants', [CommercantController::class, 'create'])->name('commercant.store');

    Route::get('/commercant/produits', [CommercantController::class, 'produits'])->name('commercant.produits');
    Route::post('/commercant/produits', [CommercantController::class, 'storeProduit'])->name('commercant.produits.store')->middleware('premium:product');
    Route::delete('/commercant/produits/{produit}', [CommercantController::class, 'destroyProduit'])->name('commercant.produits.destroy');
    Route::get('/commercant/profil', [CommercantController::class, 'profil'])->name('commercant.profil');
    Route::put('/commercant/profil', [CommercantController::class, 'updateProfil'])->name('commercant.profil.update');
    Route::post('/commercant/produits/{id}', [CommercantController::class, 'updateProduit'])->name('commercant.produits.update');
    Route::get('/commercant/{commercant}', [CommercantController::class, 'show'])->name('commercant.show');
    Route::post('/commercant/{commercantId}/rate', [CommercantController::class, 'rate']);
    
    Route::put('/user/notifications', [UserController::class, 'updateNotifications'])->name('user.notifications.update');   


    
    Route::get('/user/badges', [UserController::class, 'badges'])->name('user.badges');
    Route::get('/user/parrainage', [UserController::class, 'getParrainage']);


    Route::post('/produits/{id}/favorite', [ProduitController::class, 'toggleFavorite']);
    Route::get('/produits/{produit}', [ProduitController::class, 'show'])->name('produits.show');


    Route::post('/parrainages/generateCode', [ParrainageController::class, 'generateCode']);
    Route::post('/parrainages/createCode', [ParrainageController::class, 'createCode']);
    // ... autres routes
        Route::post('/parrainages/register', [ParrainageController::class, 'registerParrainage']);
    Route::post('/parrainages/validate/{userId}', [ParrainageController::class, 'validateParrainage']);
    Route::get('/parrainages/dashboard', [ParrainageController::class, 'dashboard']);
    Route::get('/parrainages/niveaux', [ParrainageController::class, 'getAllNiveaux']);

    Route::post('/produits/{id}/boost', [ProduitController::class, 'boost']);
    Route::post('/record_view', [ProduitController::class, 'recordView']);


    Route::get('/stats', [StatsController::class, 'index']);

    Route::get('/chat/{receiverId}', [ChatController::class, 'index']);
    Route::post('/chat/{receiverId}', [ChatController::class, 'store']);
    Route::put('/messages/mark-all-as-read', [ChatController::class, 'markAllAsRead']);
    


Route::post('/upgrade-to-premium', [SubscriptionController::class, 'upgradeToPremium']);


    Route::post('/acheter-jetons', [JetonController::class, 'acheterJetons']);

    Route::get('/jeton-transactions/{userId}', [JetonController::class, 'getUserTransactions']);
});