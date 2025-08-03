<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function upgradeToPremium(Request $request)
    {
        $user = $request->user;

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        // Valider le type d'abonnement
        $validated = $request->validate([
            'subscription_type' => 'required|in:monthly,yearly',
        ]);

        $subscriptionType = $validated['subscription_type'];
        $amount = $subscriptionType === 'monthly' ? 5000 : 50000; // En FCFA

        // Simuler un paiement (à remplacer par une intégration réelle)
        $paymentSuccess = true; // Simule un paiement réussi
        if (!$paymentSuccess) {
            return response()->json(['message' => 'Échec du paiement'], 400);
        }

        // Mettre à jour is_premium à 1
        $user->update(['is_premium' => 1]);

        // Optionnel : Gérer la durée (mensuel ou annuel)
        $trialOrSubscriptionEndsAt = $subscriptionType === 'monthly' ? now()->addMonth() : now()->addYear();
        $user->update(['subscription_ends_at' => $trialOrSubscriptionEndsAt]);

        return response()->json([
            'message' => 'Abonnement Premium activé avec succès',
            'subscription_type' => $subscriptionType,
            'ends_at' => $user->subscription_ends_at,
            'user' => $user,
        ], 200);
    }
}