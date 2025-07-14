<?php

// app/Http/Controllers/LitigeController.php
namespace App\Http\Controllers;

use App\Models\Litige;
use App\Models\Commande;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class LitigeController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'commande_id' => 'required|uuid|exists:commandes,id',
            'description' => 'required|string',
            'preuves' => 'nullable|array',
            'preuves.*' => 'string', // URLs des preuves
        ]);

        $commande = Commande::find($request->commande_id);
        if ($commande->acheteur_id !== auth()->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        if ($commande->litige) {
            return response()->json(['message' => 'Un litige existe déjà pour cette commande'], 400);
        }

        $litige = new Litige([
            'id' => Uuid::uuid4()->toString(),
            'commande_id' => $request->commande_id,
            'user_id' => auth()->user()->id,
            'description' => $request->description,
            'statut' => 'ouvert',
            'preuves' => $request->preuves,
        ]);
        $litige->save();

        $commande->statut = 'litige';
        $commande->paiement_statut = 'en_attente';
        $commande->save();

        return response()->json(['message' => 'Litige signalé', 'litige' => $litige], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|in:ouvert,résolu,rejeté',
        ]);

        $litige = Litige::findOrFail($id);
        $commande = $litige->commande;

        if ($commande->commercant->user_id !== auth()->user()->id && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $litige->statut = $request->statut;
        if ($request->statut === 'résolu') {
            $commande->paiement_statut = 'remboursé';
            $commande->statut = 'livrée';
        } elseif ($request->statut === 'rejeté') {
            $commande->paiement_statut = 'payé';
            $commande->statut = 'livrée';
        }
        $litige->save();
        $commande->save();

        return response()->json(['message' => 'Litige mis à jour', 'litige' => $litige]);
    }
}