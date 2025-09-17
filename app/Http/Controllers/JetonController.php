<?php
// app/Http/Controllers/JetonController.php
namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\JetonTransaction;
use MeSomb\Util\RandomGenerator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use MeSomb\Operation\PaymentOperation;

class JetonController extends Controller
{
    public function acheterJetons(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        // Valider les données
        $validated = $request->validate([
            'nombre_jetons' => 'required|integer|min:10',
            'payment_service' => 'required|in:ORANGE,MTN,AIRTEL',
            'phone_number' => 'required|regex:/^6[0-9]{8}$/',
        ]);

        
        $nombreJetons = $validated['nombre_jetons'];
        $paymentService = $validated['payment_service'];
        $phoneNumber = $validated['phone_number'];
        $montant = $nombreJetons * 100; // 25 FCFA par jeton
       

        // Initialiser MeSomb
        $mesomb = new PaymentOperation(
            env('MESOMB_APPLICATION_KEY'),
            env('MESOMB_ACCESS_KEY'),
            env('MESOMB_SECRET_KEY'),
        );

        $nonce = RandomGenerator::nonce();


        // return response()->json([
        //     'amount' => $montant, // Convertir en centimes
        //     'service' => $paymentService,
        //     'payer' => $phoneNumber,
        //     'nonce' => $nonce,
        // ], 401);
        // Appel à makeCollect
        $response = $mesomb->makeCollect([
            'amount' => $montant, // Convertir en centimes
            'service' => $paymentService,
            'payer' => $phoneNumber,
            'nonce' => $nonce,
        ]);

        if ($response->isOperationSuccess()) {
            // Enregistrer la transaction réussie
            JetonTransaction::create([
                'user_id' => $user->id,
                'nombre_jetons' => $nombreJetons,
                'montant' => $montant,
                'methode_paiement' => $paymentService,
                'phone_number' => $phoneNumber,
                'transaction_id_mesomb' => $nonce,
                'statut' => 'réussi',
                'date_transaction' => now('Africa/Douala'),
            ]);

            // Mettre à jour le solde de jetons de l'utilisateur (à implémenter si nécessaire)
            // Exemple :
             $user->update(['jetons' => $user->jetons + $nombreJetons]);

            return response()->json([
                'message' => 'Achat de ' . $nombreJetons . ' jetons réussi',
                'montant' => $montant . ' FCFA',
                // 'transaction_id' => $nonce,
            ], 200);
        } else {
            // Enregistrer la transaction échouée
            JetonTransaction::create([
                'user_id' => $user->id,
                'nombre_jetons' => $nombreJetons,
                'montant' => $montant,
                'phone_number' => $phoneNumber,

                'methode_paiement' => $paymentService,
                'transaction_id_mesomb' => null,
                'statut' => 'échoué',
                'date_transaction' => now(),
            ]);

            // Gérer les erreurs de MeSomb
            // $errorDetails = $response->getErrorDetails() ?? [];
            // $errorCode = $errorDetails['errorCode'] ?? 'UNKNOWN_ERROR';
            // $errorMessage = $errorDetails['errorMessage'] ?? 'Une erreur inconnue est survenue.';
            // $additionalDetails = $errorDetails['details'] ?? [];

            return response()->json([
                'message' => 'Échec de l\'achat de jetons',
                // 'error_code' => $errorCode,
                // 'error_message' => $errorMessage,
                // 'details' => $additionalDetails,
            ], 400);
        }
    }

    // app/Http/Controllers/JetonController.php
    public function getUserTransactions($userId)
    {
        $transactions = JetonTransaction::where('user_id', $userId)->get();
        return response()->json($transactions);
    }
}