<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\JetonOffer;
use App\Models\JetonTrade;
use Illuminate\Http\Request;
use MeSomb\Util\RandomGenerator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use MeSomb\Operation\PaymentOperation;

class JetonMarketController extends Controller
{

    
    public function index(Request $request)
{
    $query = JetonOffer::with('user.commercant')->where('statut', 'disponible')->with('user'); // Charger les détails de l'utilisateur (vendeur)

    // Appliquer le filtre sur la quantité si présent
    if ($request->has('quantity_min')) {
        $query->where('nombre_jetons', '>=', $request->quantity_min);
    }

    // Pagination avec ordre aléatoire
    $perPage = 10; // Nombre d'offres par page
    $page = $request->input('page', 1); // Page par défaut = 1
    $offers = $query->inRandomOrder()->paginate($perPage, ['*'], 'page', $page);

    return response()->json([   
        'data' => $offers->items(),
        'current_page' => $offers->currentPage(),
        'last_page' => $offers->lastPage(),
        'total' => $offers->total(),
    ], 200);
}

    public function buy($offer_id, Request $request)
    {
        $acheteur = Auth::user();

        if (!$acheteur) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        // Valider et récupérer l'offre avec le portefeuille du vendeur
        $offer = JetonOffer::with(['user', 'wallet'])->findOrFail($offer_id);

        if ($offer->nombre_jetons <= 0) {
            return response()->json(['message' => 'Offre épuisée'], 400);
        }

        // Calcul des montants
        $montantTotal = $offer->total_prix;
        $commission = $montantTotal * 0.10; // 5% de commission
        $montantNet = $montantTotal - $commission; // Montant net pour le vendeur

        // Validation des données de paiement (utilisation d'un wallet_id)
        $validated = $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
        ]);

        // Récupérer le portefeuille de l'acheteur
        $walletAcheteur = Wallet::where('id', $validated['wallet_id'])
            ->where('user_id', $acheteur->id)
            ->firstOrFail();
            
            
            $paymentService = $walletAcheteur->payment_service;
            $phoneNumber = $walletAcheteur->phone_number;
            
            // Initialisation de MeSomb
            $mesomb = new PaymentOperation(
                env('MESOMB_APPLICATION_KEY'),
                env('MESOMB_ACCESS_KEY'),
                env('MESOMB_SECRET_KEY')
            );
            

        $nonce = RandomGenerator::nonce();

        // Collecter le paiement auprès de l'acheteur (montant total incluant la commission)
        // return response()->json(['message' => [
        //     'amount' => $montantTotal,
        //     'service' => $paymentService,
        //     'payer' => $phoneNumber,
        //     'nonce' => $nonce,
        // ]], 400);
        $paymentResponse = $mesomb->makeCollect([
            'amount' => $montantTotal,
            'service' => $paymentService,
            'payer' => $phoneNumber,
            'nonce' => $nonce,
        ]);



        if (!$paymentResponse->isOperationSuccess()) {
            // Enregistrer l'échec de la transaction
            JetonTrade::create([
                'vendeur_id' => $offer->user_id,
                'acheteur_id' => $acheteur->id,
                'offer_id' => $offer->id,
                'nombre_jetons' => $offer->nombre_jetons,
                'montant_total' => $montantTotal,
                'commission_plateforme' => $commission,
                'montant_net_vendeur' => $montantNet,
                'methode_paiement' => 'mesomb',
                'transaction_id_mesomb_vendeur' => null,
                'transaction_id_mesomb_plateforme' => null,
                'statut' => 'echec',
                'date_transaction' => now(),
            ]);

            return response()->json(['message' => 'Échec du paiement : vérifiez vos informations'], 400);
        }

        // Transférer le montant net au vendeur (après déduction de la commission) via son portefeuille
        $depositNonce = RandomGenerator::nonce();
        $depositResponse = $mesomb->makeDeposit([
            'amount' => $montantNet,
            'service' => $offer->wallet->paymenservice,
            'recipient' => $offer->wallet->phone_number,
            'nonce' => $depositNonce,
        ]);

        if (!$depositResponse->isOperationSuccess()) {
            // Enregistrer l'échec du transfert
            JetonTrade::create([
                'vendeur_id' => $offer->user_id,
                'acheteur_id' => $acheteur->id,
                'offer_id' => $offer->id,
                'nombre_jetons' => $offer->nombre_jetons,
                'montant_total' => $montantTotal,
                'commission_plateforme' => $commission,
                'montant_net_vendeur' => $montantNet,
                'methode_paiement' => 'mesomb',
                'transaction_id_mesomb_vendeur' => null,
                'transaction_id_mesomb_plateforme' => null,
                'statut' => 'echec',
                'date_transaction' => now(),
            ]);

            return response()->json(['message' => 'Échec du transfert au vendeur : vérifiez vos informations'], 400);
        }

        // Enregistrer la transaction réussie
        $trade = JetonTrade::create([
            'vendeur_id' => $offer->user_id,
            'acheteur_id' => $acheteur->id,
            'offer_id' => $offer->id,
            'nombre_jetons' => $offer->nombre_jetons,
            'montant_total' => $montantTotal,
            'commission_plateforme' => $commission,
            'montant_net_vendeur' => $montantNet,
            'methode_paiement' => 'mesomb',
            'transaction_id_mesomb_vendeur' => $depositResponse->getTransactionId() ?? $depositNonce,
            'transaction_id_mesomb_plateforme' => null, // Pas de transfert séparé pour la commission
            'statut' => 'confirmé',
            'date_transaction' => now(),
        ]);

        // Mettre à jour les jetons de l'acheteur
        $acheteur->update(['jetons' => $acheteur->jetons + $offer->nombre_jetons]);
        // Le vendeur reçoit l'argent via MeSomb, pas besoin d'ajuster ses jetons ici

        // Supprimer l'offre
        $offer->statut('vendu');
$offer->save();
        return response()->json(['message' => 'Achat réussi', 'trade' => $trade], 200);
    }

   
}