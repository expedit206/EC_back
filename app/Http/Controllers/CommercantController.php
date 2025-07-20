<?php

namespace App\Http\Controllers;

use App\Models\Produit;
use App\Models\Commercant;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ProductFavorite;
use App\Http\Controllers\Controller;

class CommercantController extends Controller
{
    public function produits(Request $request)
    {
        $user = $request->user->load('commercant');
        // return response()->json($);
        if (!$user->commercant) {
            return response()->json(['message' => 'Accès réservé aux commerçants'], 403);
        }

        $produits = Produit::where('commercant_id', $user->commercant->id)
            ->with('category')
            ->withCount('favorites') // Charger le nombre de favoris
            ->withCount('views')    // Charger le nombre de vues
            ->get();
        // return response()->json(['produits' => 'produits']);
        return response()->json(['produits' => $produits]);
    }

    public function storeProduit(Request $request)
    {
        $user = $request->user;
        if (!$user->commercant) {
            return response()->json(['message' => 'Accès réservé aux commerçants'], 403);
        }
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix' => 'required|numeric|min:0',
            'photo_url' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'collaboratif' => 'boolean',
            'marge_min' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            // 'ville' => 'required|string',
        ]);
        
        $produit = Produit::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'commercant_id' => $user->commercant->id,
            'category_id' => $validated['category_id'],
            'nom' => $validated['nom'],
            'description' => $validated['description'],
            'prix' => $validated['prix'],
            'photo_url' => $validated['photo_url'],
            'collaboratif' => $validated['collaboratif'] ?? false,
            'marge_min' => $validated['marge_min'],
            'quantite' => $validated['stock'],
            'ville' => $validated['ville']??'aucun',
        ]);
        // return response()->json(['request' => $user->commercant->id]);

        return response()->json(['produit' => $produit], 201);
    }

    public function destroyProduit(Produit $produit, Request $request )
    {
        // $commercant = $request->user->load('commercant');

        // $produit = Produit::where('commercant_id', $commercant->id)->findOrFail($id);
        $produit->delete();
        return response()->json(['message' => 'Produit supprimé']);
    }

    public function profil(Request $request)
    {
        $commercant = $request->user->load('commercant',);
        return response()->json(['commercant' => $commercant]);
    }

    public function show(Commercant $commercant)
{
    return response()->json(['commercant' =>  $commercant]);
}
    
    public function updateProfil(Request $request)
    {
        $commercant = $request->user->load('commercant');

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ville' => 'nullable|string',
        ]);

        $commercant->update($validated);
        return response()->json(['commercant' => $commercant]);
    }

    public function updateProduit(Request $request, $id)
    {
        $user = $request->user;
        if (!$user->commercant) {
            return response()->json(['message' => 'Accès réservé aux commerçants'], 403);
        }

        $produit = Produit::where('id', $id)->where('commercant_id', $user->commercant->id)->firstOrFail();

        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'photo_url' => 'nullable|url',
            'category_id' => 'required|exists:categories,id',
            'collaboratif' => 'boolean',
            'marge_min' => 'nullable|numeric|min:0|required_if:collaboratif,true',
        ]);

        $produit->update([
            'nom' => $data['nom'],
            'description' => $data['description'],
            'prix' => $data['prix'],
            'stock' => $data['stock'],
            'photo_url' => $data['photo_url'],
            'category_id' => $data['category_id'],
            'collaboratif' => $data['collaboratif'],
            'marge_min' => $data['collaboratif'] ? $data['marge_min'] : null,
        ]);

        return response()->json(['message' => 'Produit modifié', 'produit' => $produit]);
    }
}