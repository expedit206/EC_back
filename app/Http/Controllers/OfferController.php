<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\JetonOffer;
use App\Models\JetonTrade;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class OfferController extends Controller
{
    public function myOffers()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $offers = JetonOffer::with(['user', 'wallet'])->where('user_id', $user->id)->get();
        return response()->json(['data' => $offers, 'message' => 'Offres récupérées avec succès'], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_jetons' => 'required|integer|min:1',
            'prix_unitaire' => 'required|numeric|min:1',
            'description' => 'nullable|string',
            'wallet_id' => 'required|exists:wallets,id',
        ]);

        $user = Auth::user();
        $totalPrice = $validated['nombre_jetons'] * $validated['prix_unitaire'];

        // Vérifier que le wallet appartient à l'utilisateur
        $wallet = Wallet::where('id', $validated['wallet_id'])->where('user_id', $user->id)->firstOrFail();

        $offer = JetonOffer::create([
            'user_id' => $user->id,
            'nombre_jetons' => $validated['nombre_jetons'],
            'prix_unitaire' => $validated['prix_unitaire'],
            'total_prix' => $totalPrice,
            'description' => $validated['description'],
            'wallet_id' => $wallet->id,
            'date_creation' => now(),
        ]);

        return response()->json(['message' => 'Offre créée avec succès!', 'offer' => $offer], 201);
    }


    public function updateOffer(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }
        
        // return response()->json([
        //     'data' => JetonOffer::all(),
        //     'message' => 'Offre mise à jour avec succès',
        // ], 200);
        $offer = JetonOffer::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        
        $validated = $request->validate([
            'nombre_jetons' => 'required|integer|min:1',
            'prix_unitaire' => 'required|numeric|min:1',
            'wallet_id' => 'required|exists:wallets,id',
        ]);

        // Vérifier que le wallet appartient à l'utilisateur
        $wallet = Wallet::where('id', $validated['wallet_id'])->where('user_id', $user->id)->firstOrFail();

        $totalPrice = $validated['nombre_jetons'] * $validated['prix_unitaire'];

        $offer->update([
            'nombre_jetons' => $validated['nombre_jetons'],
            'prix_unitaire' => $validated['prix_unitaire'],
            'total_prix' => $totalPrice,
            'wallet_id' => $wallet->id,
        ]);

        return response()->json([
            'data' => $offer,
            'message' => 'Offre mise à jour avec succès',
        ], 200);
    }

    /**
     * Supprimer une offre.
     */
    public function destroyOffer($id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $offer = JetonOffer::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        // Vérifier si l'offre a été achetée (statut dans jeton_trades)
        if (JetonTrade::where('offer_id', $offer->id)->where('statut', 'confirmé')->exists()) {
            return response()->json(['message' => 'Cette offre a déjà été achetée et ne peut pas être supprimée.'], 400);
        }

        $offer->delete();

        return response()->json(['message' => 'Offre supprimée avec succès'], 200);
    }

}