<?php

// app/Http/Controllers/ProduitController.php
namespace App\Http\Controllers;

use App\Models\Produit;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class ProduitController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'boutique_id' => 'required|uuid|exists:boutiques,id',
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix' => 'required|numeric|min:0',
            'quantite' => 'required|integer|min:0',
            'photo_url' => 'nullable|string',
            'collaboratif' => 'boolean',
            'marge_min' => 'nullable|numeric|min:0',
        ]);

        $user = auth()->user();
        $boutique = Boutique::find($request->boutique_id);

        if (!$boutique || $boutique->commercant->user_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé à cette boutique'], 403);
        }

        $produit = new Produit([
            'id' => Uuid::uuid4()->toString(),
            'boutique_id' => $request->boutique_id,
            'nom' => $request->nom,
            'description' => $request->description,
            'prix' => $request->prix,
            'quantite' => $request->quantite,
            'photo_url' => $request->photo_url,
            'collaboratif' => $request->collaboratif ?? false,
            'marge_min' => $request->marge_min,
        ]);
        $produit->save();

        return response()->json(['message' => 'Produit créé', 'produit' => $produit], 201);
    }

    public function index()
    {
        // die;
        // return response()->json(['produits' => 'echec']);
        // return response()->json(['produits' => 'produits']);
        $products = Produit::paginate(12); // 12 produits par page
        return response()->json([
            'data' => $products->items(),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
        ]);
        // return response()->json(['produits' => $produits]);
    }
}