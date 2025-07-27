<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Produit;
use App\Models\Commercant;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ProductFavorite;
use App\Models\ParrainageNiveau;
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

    public function createCommercant(Request $request)
    {
        $user = $request->user;

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'ville' => 'required|string|max:255',
        ]);

        // Créer le compte commerçant (actif par défaut)
        $commercant = Commercant::create([
            'user_id' => $user->id,
            'nom' => $validated['nom'],
            'ville' => $validated['ville'],
            'active_products' => 0,
        ]);

        // Mettre à jour le parrainage si l'utilisateur a un parrain
        if ($user->parrain_id) {
            $this->updateParrainage($user->parrain_id);
        }

        return response()->json(['message' => 'Compte commerçant créé avec succès', 'commercant' => $commercant], 201);
    }

    private function updateParrainage($parrain_id)
    {
        $parrain = User::with('niveaux_users')->find($parrain_id);
        if (!$parrain) {
            return;
        }

        // Compter uniquement les filleuls commerçants
        $filleuls_commercants = User::where('parrain_id', $parrain_id)
            ->whereHas('commercant')
            ->count();

        // Récupérer ou créer l'entrée dans niveaux_users
        $niveau_actuel = $parrain->niveaux_users()->where('statut', 'actif')->latest('date_atteinte')->first();
        $niveau_id = $this->determinerNiveau($filleuls_commercants);

        if (!$niveau_actuel || $niveau_actuel->niveau_id != $niveau_id) {
            $niveau = ParrainageNiveau::find($niveau_id);
            $niveau_user = $parrain->niveaux_users()->create([
                'niveau_id' => $niveau_id,
                'date_atteinte' => now(),
                'jetons_attribues' => $niveau->jetons_bonus,
                'nombre_filleuls_actuels' => $filleuls_commercants,
            ]);

            // Mettre à jour les jetons totaux
            $parrain->increment('jetons', $niveau->jetons_bonus);
            $parrain->save();
        } else {
            $niveau_actuel->update(['nombre_filleuls_actuels' => $filleuls_commercants]);
        }
    }

    private function determinerNiveau($filleuls_commercants)
    {
        $niveaux = ParrainageNiveau::orderBy('filleuls_requis', 'desc')->get();
        foreach ($niveaux as $niveau) {
            if ($filleuls_commercants >= $niveau->filleuls_requis) {
                return $niveau->id;
            }
        }
        return 1; // Niveau par défaut (Initié)
    }
}