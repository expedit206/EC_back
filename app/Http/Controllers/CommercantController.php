<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Produit;
use App\Models\Commercant;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
// use Intervention\Image\Image;
use Intervention\Image\Format;
use App\Models\ProductFavorite;
use App\Models\ParrainageNiveau;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class CommercantController extends Controller
{
    public function produits(Request $request)
    {
        $user = $request->user()->load('commercant');
        // return response()->json($);
        if (!$user->commercant) {
            return response()->json(['message' => 'Accès réservé aux commerçants'], 403);
        }

        $produits = Produit::where('commercant_id', $user->commercant->id)
            ->with('category')
            ->withCount('favorites') // Charger le nombre de favoris
            ->withCount('views')    // Charger le nombre de vues
            ->orderBy('created_at', 'desc')    // Charger le nombre de vues
            ->get();
        // return response()->json(['produits' => 'produits']);
        return response()->json(['produits' => $produits]);
    }

    // use Intervention\Image\Facades\Image;

    // use Illuminate\Http\Request;
    // use Intervention\Image\Laravel\Facades\Image;

    public function storeProduit(Request $request)
    {
        $user = $request->user();
        if (!$user->commercant) {
            return response()->json(['message' => 'Utilisateur non autorisé'], 403);
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix' => 'required|numeric|min:0',
            'photos' => 'required|array',
            'photos.*' => 'image|max:2048',
            'category_id' => 'required|exists:categories,id',
            'collaboratif' => 'required',
            'marge_min' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'ville' => 'nullable|string',
        ]);

        $photos = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $filename = time() . '_' . pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg';
                $destinationPath = public_path('storage/produits');

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                // Compression avec redimensionnement, suppression des métadonnées, puis encodage
                $image = Image::read($photo)
                    // ->resize(1200, 600, function ($constraint) {
                    //     $constraint->aspectRatio();
                    //     $constraint->upsize();
                    // })
                    ->encodeByExtension('jpg', quality: 10);
                $image->save($destinationPath . '/' . $filename);

                $photos[] = asset('storage/produits/' . $filename);
            }
        }

        $produit = Produit::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'commercant_id' => $user->commercant->id,
            'category_id' => $validated['category_id'],
            'nom' => $validated['nom'],
            'description' => $validated['description'],
            'prix' => $validated['prix'],
            'photos' => $photos,
            'collaboratif' => $validated['collaboratif'] == '0' ? 0 : 1,
            'marge_min' => $validated['marge_min'] ?? null,
            'quantite' => $validated['stock'],
            'ville' => $validated['ville'] ?? 'aucun',
        ]);

        return response()->json(['produit' => $produit], 201);
    }
  

    public function updateProduit(Request $request, $id)
    {
        $user = $request->user();
        $produit = Produit::where('commercant_id', $user->commercant->id)->findOrFail($id);

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix' => 'required|numeric|min:0',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'old_photos' => 'nullable|array',
            'old_photos.*' => 'string',
            'category_id' => 'required|exists:categories,id',
            'collaboratif' => 'boolean',
            'marge_min' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'ville' => 'nullable|string',
        ]);

        // Photos gardées par l’utilisateur
        $oldPhotos = $validated['old_photos'] ?? [];

        // Anciennes photos en BDD
        $existingPhotos = $produit->photos ?? [];

        // Supprimer celles qui ne sont plus dans old_photos
        foreach ($existingPhotos as $oldPhoto) {
            if (!in_array($oldPhoto, $oldPhotos)) {
                $path = public_path(str_replace(asset(''), '', $oldPhoto));
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }

        // Ajouter les nouvelles
        $photos = $oldPhotos; // on garde celles restantes
        
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $filename = time() . '_' . $photo->getClientOriginalName();
                $photo->move(public_path('storage/produits'), $filename);
                
                $photos[] = asset('storage/produits/' . $filename);
            }
        }
        
        // return response()->json(['produit' => $request->hasFile('photos')], 200);
        // Mise à jour
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
        // $commercant = $request->user()->load('commercant');

        // $produit = Produit::where('commercant_id', $commercant->id)->findOrFail($id);
        $produit->delete();
        return response()->json(['message' => 'Produit supprimé']);
    }

    public function profil(Request $request)
    {
        $request->user()->load('commercant',);
        $commercant = $request->user()->commercant;
        return response()->json(['commercant' => '$commercant']);
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
        // Charger l'utilisateur authentifié avec sa relation commerçant
        $user = $request->user()->load('commercant');

        // Vérifier si l'utilisateur a un profil commerçant
        if (!$user->commercant) {
            return response()->json(['message' => 'Profil commerçant non trouvé.'], 404);
        }

        // Validation des données
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'telephone' => 'required|string|max:20',
            'ville' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Limite à 2 Mo
        ]);

        // Préparer les données pour la mise à jour
        $data = [
            'nom' => $validated['nom'],
            'description' => $validated['description'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'],
            'ville' => $validated['ville'],
        ];

        // Gérer le téléchargement du logo si présent
        if ($request->hasFile('logo')) {
            // Supprimer l'ancien logo si existant
            if ($user->commercant->logo && file_exists(public_path('storage/' . $user->commercant->logo))) {
                unlink(public_path('storage/' . $user->commercant->logo));
            }

            // Traiter la nouvelle photo avec Intervention Image
            $file = $request->file('logo');
            $filename = time() . '_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg'; // Forcer .jpg
            $destinationPath = public_path('storage/commercant/logos');

            // Créer le dossier s'il n'existe pas
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // Compression et redimensionnement
            $image = Image::read($file)
             
                ->encodeByExtension('jpg', quality: 15); // Compression à 75%

            $image->save($destinationPath . '/' . $filename);

            // Enregistrer le chemin relatif en BDD
            $data['logo'] = 'commercant/logos/' . $filename;
        }

        // Mettre à jour le profil du commerçant
        $user->commercant->update($data);

        // Recharger la relation pour retourner les données mises à jour
        $updatedCommercant = $user->commercant->fresh();

        return response()->json([
            'success' => true,
            'commercant' => $updatedCommercant,
        ], 200);
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
        } else {
            $niveau_actuel->update(['nombre_filleuls_actuels' => $filleuls_commercants]);
        }
        $parrain->jetons+=1;
        $parrain->save();
        
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