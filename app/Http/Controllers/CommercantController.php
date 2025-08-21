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
use Illuminate\Support\Facades\Storage;

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
        $user = $request->user();
        // return response()->json(['message' => $request->all()]);
        if (!$user->commercant) {
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix' => 'required|numeric|min:0',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:2048', // Limite à 2Mo par image
            'category_id' => 'required|exists:categories,id',
            'collaboratif' => 'required',
            'marge_min' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'ville' => 'nullable|string',
        ]);

        $photos = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('produits', 'public'); // Stocke dans storage/app/public/produits
                $photos[] = asset('storage/' . $path); // Génère l'URL publique
            }
        }
        // return response()->json(['message' => $photos]);
        $produit = Produit::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'commercant_id' => $user->commercant->id,
            'category_id' => $validated['category_id'],
            'nom' => $validated['nom'],
            'description' => $validated['description'],
            'prix' => $validated['prix'],
            'photos' => $photos, // Stocker les URLs en JSON
            // 'photos' => json_encode($photos), // Stocker les URLs en JSON
            'collaboratif' => $validated['collaboratif'] == 'false' ? 0 : 1,
            'marge_min' => $validated['marge_min'] ?? null,
            'quantite' => $validated['stock'],
            'ville' => $validated['ville'] ?? 'aucun',
        ]);

        return response()->json(['produit' => $produit], 201);
    }

    // Pour la mise à jour
    public function updateProduit(Request $request, $id)
    {
        // Récupérer l'utilisateur authentifié
        $user = $request->user();
        $produit = Produit::where('commercant_id', $user->commercant->id)->findOrFail($id);

        // Validation des données
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix' => 'required|numeric|min:0',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:2048',
            'category_id' => 'required|exists:categories,id',
            'collaboratif' => 'boolean',
            'marge_min' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'ville' => 'nullable|string',
        ]);

        // Gérer les anciennes photos (suppression)
        $oldPhotos = $produit->photos ?? [];
        if (!empty($oldPhotos) && is_array($oldPhotos)) {
            foreach ($oldPhotos as $oldPhoto) {
                // Extraire le chemin relatif à partir de l'URL (ex. : /storage/produits/filename.png -> produits/filename.png)
                $path = parse_url($oldPhoto, PHP_URL_PATH);
                $relativePath = str_replace('/storage/', '', $path); // Récupère uniquement le chemin relatif (ex. : produits/filename.png)
                if (Storage::disk('public')->exists($relativePath)) {
                    Storage::disk('public')->delete($relativePath);
                }
            }
        }

        // Gérer les nouvelles photos
        $photos = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photoPath = $photo->store('produits', 'public');
                $photos[] = asset('storage/' . $photoPath); // Stocker l'URL complète
            }
        } else {
            // Si aucune nouvelle photo n'est uploadée, conserver les anciennes (si aucune suppression n'est demandée)
            $photos = $oldPhotos;
        }

        // Mettre à jour le produit
        $produit->update([
            'nom' => $validated['nom'],
            'description' => $validated['description'],
            'prix' => $validated['prix'],
            'photos' => $photos,
            'category_id' => $validated['category_id'],
            'collaboratif' => $validated['collaboratif'] ?? false,
            'marge_min' => $validated['marge_min'] ?? null,
            'quantite' => $validated['stock'],
            'ville' => $validated['ville'] ?? 'aucun',
        ]);

        return response()->json(['produit' => $produit], 200);
    }

    public function destroyProduit(Produit $produit, Request $request)
    {
        // $commercant = $request->user->load('commercant');

        // $produit = Produit::where('commercant_id', $commercant->id)->findOrFail($id);
        $produit->delete();
        return response()->json(['message' => 'Produit supprimé']);
    }

    public function profil(Request $request)
    {
        $request->user->load('commercant',);
        $commercant = $request->user->commercant;
        return response()->json(['commercant' => $commercant]);
    }

    public function show($id)
    {
        $commercant = Commercant::with(['produits'])->findOrFail($id);
        $averageRating = $commercant->average_rating; // Utilise l'attribut calculé
        $voteCount = $commercant->ratings()->count();
        return response()->json([
            'commercant' => $commercant,
            'vote_count' => $voteCount,
            'average_rating' => $averageRating,
        ]);
    }

    public function rate(Request $request, $id)
    {
        $commercant = Commercant::findOrFail($id);
        $user = $request->user();

        // Vérifier si l'utilisateur a déjà noté
        $existingRating = $commercant->ratings()->where('user_id', $user->id)->first();
        if ($existingRating) {
            return response()->json(['message' => 'Vous avez déjà noté ce commerçant.'], 400);
        }

        $request->validate([
            'rating' => 'required|integer|between:1,5',
        ]);

        // return response()->json(['message' => $request->all()]);


        $commercant->ratings()->create([
            'user_id' => $user->id,
            'commercant_id' => $id,
            'rating' => $request->rating,
        ]);

        $averageRating = $commercant->average_rating;

        return response()->json([
            'message' => 'Note enregistrée avec succès.',
            'average_rating' => $averageRating,
        ]);
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


    public function create(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'ville' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'logo' => 'nullable|string|max:255', // URL du logo


        ]);


        // Créer le compte commerçant (actif par défaut)
        $commercant = Commercant::create([
            'user_id' => $user->id,
            'nom' => $validated['nom'] ?? null,
            'ville' => $validated['ville'] ?? null,
            'description' => $validated['description'] ?? null,
            'logo' => $validated['logo'] ?? null,

        ]);

        // Mettre à jour le parrainage si l'utilisateur a un parrain
        if ($user->parrain_id) {
            // return response()->json(['m²essage' => $this->updateParrainage($user->parrain_id) ]);
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

                'user_id' => $parrain->id,
                'niveau_id' => $niveau_id,
                'date_atteinte' => now(),
                'jetons_attribues' => $niveau->jetons_bonus,
                'nombre_filleuls_actuels' => $filleuls_commercants,
            ]);
            // return response()->json(['message' => $filleuls_commercants]);

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

    public function getParrainage(Request $request)
    {
        $user = $request->user();
        $filleuls_commercants = User::where('parrain_id', $user->id)->whereHas('commercant')->count();
        $niveau = $user->niveaux_users()->where('statut', 'actif')->with('niveau')->latest('date_atteinte')->first();

        $prochain_niveau = ParrainageNiveau::where('filleuls_requis', '>', ($niveau ? $niveau->nombre_filleuls_actuels : 0))
            ->orderBy('filleuls_requis', 'asc')
            ->first();

        return response()->json([
            'niveau' => $niveau ? $niveau->niveau : ParrainageNiveau::find(1),
            'jetons' => $user->jetons,
            'avantages' => $niveau ? json_decode($niveau->niveau->avantages) : [],
            'filleuls_commercants' => $filleuls_commercants,
            'prochain_seuil' => $prochain_niveau ? $prochain_niveau->filleuls_requis : 1000,
        ]);
    }
}
