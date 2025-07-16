<?php

namespace App\Http\Controllers;

use App\Models\Panier;
use App\Models\Product;
use App\Models\Produit;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PanierController extends Controller
{

    public function index (Request  $request)
    {
        $user = $request->user->load('commercant');
        $items = Panier::with('produit')->where('user_id', $user->id)->get();
        return response()->json(['items' => $items]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate(['quantite' => 'required|integer|min:1']);
        $item = Panier::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $item->update(['quantite' => $data['quantite']]);
        return response()->json(['message' => 'Quantité mise à jour']);
    }



    public function store(Request $request)
    {
        $user = $request->user;
        $data = $request->validate([
            'produit_id' => 'required|exists:produits,id',
        ]);
     
        $produit = Produit::findOrFail($data['produit_id']);
        if ($user->commercant && $user->commercant->id === $produit->commercant_id) {
            return response()->json(['message' => 'Vous ne pouvez pas ajouter votre propre produit au panier'], 422);
        }

        $item = Panier::where('user_id', $user->id)
            ->where('produit_id', $data['produit_id'])
            ->first();

        if ($item) {
            $item->update(['quantite' => $item->quantite + 1]);
        } else {
            $item = Panier::create([
                'id' => \Str::uuid(),
                'user_id' => $user->id,
                'produit_id' => $data['produit_id'],
                'quantite' => 1,
            ]);
        }

        return response()->json(['message' => 'Produit ajouté au panier', 'item' => $item]);
    }

    public function destroy($produit_id, Request $request)
    {
        $user = $request->user;
        Panier::where('user_id', $user->id)
            ->where('produit_id', $produit_id)
            ->delete();
        return response()->json(['message' => 'Produit retiré du panier']);
    }

}