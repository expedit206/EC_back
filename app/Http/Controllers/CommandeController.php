<?php

// app/Http/Controllers/CommandeController.php
namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\Produit;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class CommandeController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'produit_id' => 'required|uuid|exists:produits,id',
            'collaborateur_id' => 'nullable|uuid|exists:users,id',
        ]);

        $produit = Produit::find($request->produit_id);
        if ($produit->quantite <= 0) {
            return response()->json(['message' => 'Produit en rupture de stock'], 422);
        }

        $montant_total = $produit->prix;
        $collaborateur = null;

        if ($request->collaborateur_id) {
            $collaboration = Collaboration::where('produit_id', $request->produit_id)
                ->where('user_id', $request->collaborateur_id)
                ->where('statut', 'validée')
                ->first();

            if (!$collaboration) {
                return response()->json(['message' => 'Collaboration non valide'], 403);
            }
            $montant_total = $collaboration->prix_revente;
            $collaborateur = $request->collaborateur_id;
        }

        $commande = new Commande([
            'id' => Uuid::uuid4()->toString(),
            'acheteur_id' => auth()->user()->id,
            'produit_id' => $request->produit_id,
            'commercant_id' => $produit->boutique->commercant_id,
            'collaborateur_id' => $collaborateur,
            'statut' => 'en_attente',
            'montant_total' => $montant_total,
            'paiement_statut' => 'en_attente',
        ]);
        $commande->save();

        // Décrementer la quantité du produit
        $produit->quantite--;
        $produit->save();

        return response()->json(['message' => 'Commande créée', 'commande' => $commande], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|in:en_attente,livrée,litige',
        ]);

        $commande = Commande::findOrFail($id);
        if ($commande->commercant->user_id !== auth()->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $commande->statut = $request->statut;
        if ($request->statut === 'livrée') {
            $commande->paiement_statut = 'payé';
            // Ajouter les gains au collaborateur si applicable
            if ($commande->collaborateur_id) {
                $collaboration = Collaboration::where('produit_id', $commande->produit_id)
                    ->where('user_id', $commande->collaborateur_id)
                    ->first();
                $collaboration->gains_totaux += ($commande->montant_total - $commande->produit->prix);
                $collaboration->save();
            }
        }
        $commande->save();

        return response()->json(['message' => 'Statut de la commande mis à jour', 'commande' => $commande]);
    }
}