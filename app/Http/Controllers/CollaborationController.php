<?php

// app/Http/Controllers/CollaborationController.php
namespace App\Http\Controllers;

use App\Models\Collaboration;
use App\Models\Produit;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class CollaborationController extends Controller
{
    public function index(Request $request)
    {
        // $user = $request->user;
        $user = $request->user;
        $collaborations = Collaboration::with('produit')->where('user_id', $user->id)->get();
        return response()->json(['collaborations' => $collaborations]);
    }
    public function store(Request $request)
    {
        $data =  $request->validate([
            'produit_id' => 'required|uuid|exists:produits,id',
            'prix_revente' => 'required|numeric|min:0',
        ]);
        $user = $request->user;
        // $produit = Produit::findOrFail($request->produit_id);
        $produit = Produit::findOrFail($data['produit_id']);
        if ($user->commercant && $user->commercant->id === $produit->commercant_id) {
            return response()->json(['message' => 'Vous ne pouvez pas collaborer sur votre propre produit'], 422);
        }
        if (!$produit->collaboratif) {
            return response()->json(['message' => 'Ce produit n’est pas ouvert à la collaboration'], 422);
        }
        if ($data['prix_revente'] < $produit->prix + ($produit->marge_min ?? 0)) {
            return response()->json(['message' => 'Le prix de revente est trop bas'], 422);
        }

        $collaboration = Collaboration::create([
            'id' => \Str::uuid(),
            'user_id' => $user->id,
            'produit_id' => $data['produit_id'],
            'prix_revente' => $data['prix_revente'],
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Demande de collaboration envoyée', 'collaboration' => $collaboration]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|in:validée,refusée',
        ]);

        $collaboration = Collaboration::findOrFail($id);
        $produit = $collaboration->produit;
        $boutique = $produit->boutique;

        if ($boutique->commercant->user_id !== auth()->user->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $collaboration->statut = $request->statut;
        $collaboration->save();

        return response()->json(['message' => 'Collaboration mise à jour', 'collaboration' => $collaboration]);
    }
}
