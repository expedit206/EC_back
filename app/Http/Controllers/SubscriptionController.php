<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MeSomb\Util\RandomGenerator;
use App\Models\PremiumTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use MeSomb\Operation\PaymentOperation;

class SubscriptionController extends Controller
{
    public function upgradeToPremium(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        // Valider le type d'abonnement
        $validated = $request->validate([
            'subscription_type' => 'required|in:monthly,yearly',
            'payment_service' => 'required|in:ORANGE,MTN',

            'phone_number' => 'required|regex:/^6[0-9]{8}$/', // 9 chiffres commençant par 6
        ]);
        // return respons   e()->json(['message' => 'Utilisateur non authentifié']);


        $subscriptionType = $validated['subscription_type'];
        $paymentService = $validated['payment_service'];
        $phoneNumber = $validated['phone_number'];
        $amount = $subscriptionType === 'monthly' ? 100 : 50000;
        $typeAbonnement = $subscriptionType === 'monthly' ? 'mensuel' : 'annuel';
        //mesomb pour paiement

        $mesomb = new PaymentOperation(
            env('MESOMB_APPLICATION_KEY'),
            env('MESOMB_ACCESS_KEY'),
            env('MESOMB_SECRET_KEY'),
        );

        $nonce = RandomGenerator::nonce();


        // Appel à makeCollect avec le numéro dynamique
        $response = $mesomb->makeCollect([
            'amount' => $amount, // Convertir en centimes
            'service' => $paymentService,
            'payer' => $phoneNumber, // Utiliser le numéro fourni
            'nonce' => $nonce,
        ]);

        if ($response->isOperationSuccess()) {


            $user->jetons +=20;
            PremiumTransaction::create([
                // 'id' => \Str::uuid(),
                'user_id' => $user->id,
                'type_abonnement' => $typeAbonnement,
                'montant' => $amount,
                'methode_paiement' => $paymentService,
                'transaction_id_mesomb' => $nonce,
                'statut' => 'réussi',
                'date_transaction' => now(),
            ]);
            // return response()->json(['message' => 'reussi du paiement'], 200);
        } else {

            PremiumTransaction::create([
                // 'id' => \Str::uuid(),
                'user_id' => $user->id,
                'type_abonnement' => $typeAbonnement,
                'montant' => $amount,
                'methode_paiement' => $paymentService,
                'transaction_id_mesomb' => $nonce,
                'statut' => 'echec',
                'date_transaction' => now(),
            ]);
            return response()->json(['message' => $response], 400);
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