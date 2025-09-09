<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $wallets = Wallet::where('user_id', $user->id)->get();

        return response()->json([
            'data' => $wallets,
            'message' => 'Portefeuilles récupérés avec succès',
        ], 200);
    }

    /**
     * Créer un nouveau portefeuille pour l'utilisateur authentifié.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $validated = $request->validate([
            'phone_number' => 'required|regex:/^6[0-9]{8}$/|unique:wallets,phone_number',
            'payment_service' => 'required|in:ORANGE,MTN',
        ]);

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'phone_number' => $validated['phone_number'],
            'payment_service' => $validated['payment_service'],
        ]);

        return response()->json([
            'data' => $wallet,
            'message' => 'Portefeuille créé avec succès',
        ], 201);
    }

    /**
     * Mettre à jour un portefeuille existant.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $wallet = Wallet::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'phone_number' => 'required|regex:/^6[0-9]{8}$/|unique:wallets,phone_number,' . $wallet->id,
            'payment_service' => 'required|in:ORANGE,MTN',
        ]);

        $wallet->update($validated);

        return response()->json([
            'data' => $wallet,
            'message' => 'Portefeuille mis à jour avec succès',
        ], 200);
    }

    /**
     * Supprimer un portefeuille.
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $wallet = Wallet::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        // Vérifier si le portefeuille est associé à une offre active
        if ($wallet->jetonOffers()->where('nombre_jetons', '>', 0)->exists()) {
            return response()->json(['message' => 'Ce portefeuille est associé à une offre et ne peut pas être supprimé.'], 400);
        }

        $wallet->delete();

        return response()->json(['message' => 'Portefeuille supprimé avec succès'], 200);
    }
}