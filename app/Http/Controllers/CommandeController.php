<?php

namespace App\Http\Controllers;

use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommandeController extends Controller
{
    public function produits()
    {
        $commercant = $request->user->load('commercant');

        $produits = Produit::where('commercant_id', $commercant->id)->get();
        return response()->json(['produits' => $produits]);
    }

    public function storeProduit(Request $request)
    {
        $commercant = $request->user->load('commercant');

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix' => 'required|numeric|min:0',
            'photo_url' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'collaboratif' => 'boolean',
            'marge_min' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'ville' => 'required|string',
        ]);

        $produit = Produit::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'commercant_id' => $commercant->id,
            'category_id' => $validated['category_id'],
            'nom' => $validated['nom'],
            'description' => $validated['description'],
            'prix' => $validated['prix'],
            'photo_url' => $validated['photo_url'],
            'collaboratif' => $validated['collaboratif'] ?? false,
            'marge_min' => $validated['marge_min'],
            'stock' => $validated['stock'],
            'ville' => $validated['ville'],
        ]);

        return response()->json(['produit' => $produit], 201);
    }

    public function destroyProduit($id)
    {
        $commercant = $request->user->load('commercant');

        $produit = Produit::where('commercant_id', $commercant->id)->findOrFail($id);
        $produit->delete();
        return response()->json(['message' => 'Produit supprimé']);
    }

    // public function profil(Request $request)
    // {
    //     return response()->json(['commercant' => 'commercant']);
    //     $commercant = $request->user->load('commercant');
    //     return response()->json(['commercant' => $commercant]);
    // }

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
}
