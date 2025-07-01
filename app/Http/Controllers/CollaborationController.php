<?php

// app/Http/Controllers/CollaborationController.php
namespace App\Http\Controllers;

use App\Models\Collaboration;
use App\Models\Produit;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class CollaborationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'produit_id' => 'required|uuid|exists:produits,id',
            'prix_revente' => 'required|numeric|min:0',
        ]);

        $produit = Produit::find($request->produit_id);
        if (!$produit->collaboratif) {
            return response()->json(['message' => 'Ce produit ne permet pas la collaboration'], 403);
        }

        if ($request->prix_revente < $produit->prix + $produit->marge_min) {
            return response()->json(['message' => 'Le prix de revente est inférieur à la marge minimale'], 422);
        }

        $collaboration = new Collaboration([
            'id' => Uuid::uuid4()->toString(),
            'produit_id' => $request->produit_id,
            'user_id' => auth()->user()->id,
            'prix_revente' => $request->prix_revente,
            'statut' => 'en_attente',
            'gains_totaux' => 0,
        ]);
        $collaboration->save();

        return response()->json(['message' => 'Demande de collaboration envoyée', 'collaboration' => $collaboration], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|in:validée,refusée',
        ]);

        $collaboration = Collaboration::findOrFail($id);
        $produit = $collaboration->produit;
        $boutique = $produit->boutique;

        if ($boutique->commercant->user_id !== auth()->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $collaboration->statut = $request->statut;
        $collaboration->save();

        return response()->json(['message' => 'Collaboration mise à jour', 'collaboration' => $collaboration]);
    }
}