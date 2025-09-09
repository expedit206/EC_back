<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Commercant;
use Illuminate\Http\Request;
use App\Models\ParrainageNiveau;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Afficher le profil de l'utilisateur connecté
     */
    public function show()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $profileData = [
            'id' => $user->id,
            'nom' => $user->nom,
            'email' => $user->email,
            'telephone' => $user->telephone,
            'parrainage_code' => $user->parrainage_code,
            'parrain_id' => $user->parrain_id,
            'created_at' => $user->created_at,
            'role' => $user->role ?? 'user', // Ajoutez une colonne 'role' dans users si nécessaire
            'parrainages' => $this->getParrainages($user->id),
            'total_gains' => $this->calculateTotalGains($user->id),
        ];

        // Si l'utilisateur est un commerçant, ajouter les détails commerçant
        if ($user->commercant) {
            $profileData['commercant'] = $this->getCommercantDetails($user->commercant);
        }

        return response()->json($profileData);
    }

    /**
     * Récupérer les détails des parrainages de l'utilisateur
     */
    protected function getParrainages($userId)
    {
        return User::where('parrain_id', $userId)->with('commercant')->get()->map(function ($filleul) {
            return [
                'filleul_nom' => $filleul->nom,
                'date_inscription' => $filleul->created_at,
                'est_commercant' => $filleul->commercant ? true : false,
            ];
        })->all();
    }

    /**
     * Récupérer les détails du commerçant
     */
    protected function getCommercantDetails($commercant)
    {
        return [
            'id' => $commercant->id,
            'nom' => $commercant->nom,
            'ville' => $commercant->ville,
            'email' => $commercant->email,
            'telephone' => $commercant->telephone,
            'photo_url' => $commercant->photo_url,
            'produits_count' => $commercant->produits()->count(),
            'statistiques' => [
                'total_views' => $commercant->produits()->sum('views_count') ?? 0,
                'popular_products' => $commercant->produits()->count(),
            ],
        ];
    }

    /**
     * Calculer les gains totaux de l'utilisateur à partir des parrainages
     */
    protected function calculateTotalGains($userId)
    {
        $commercantsParraines = User::where('parrain_id', $userId)->whereHas('commercant')->get();
        $gains = 0;

        foreach ($commercantsParraines as $commercant) {
            $gains += 500; // Bonus de 500 FCFA par commerçant actif
        }

        return $gains;
    }

    public function updatePassword(Request $request)
    {
        // Validation des champs
        
        
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:8',
        ]);

        $user = $request->user(); // Récupération directe de l'utilisateur connecté

        // Vérifier que l'ancien mot de passe est correct
        if (!Hash::check($request->current_password, $user->mot_de_passe)) {
          
            return response()->json(['message'=> 'Le mot de passe actuel est incorrect.'], 404);
            // return response()->json('current_password' , 'Le mot de passe actuel est incorrect.');
        }

        // Mettre à jour le mot de passe
        $user->update([
            'mot_de_passe' => \Hash::make($request->new_password),
        ]);
        return response()->json(['success'=> 'Mot de passe modifié avec succès !']);


    }
    
    public function updateProfilePhoto(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'photo' => 'required|image|max:2048', // Limite à 2 Mo et accepte uniquement les images
        ]);

        // Supprimer l'ancienne photo si elle existe
        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }

        // Stocker la nouvelle photo
        $photoPath = $request->file('photo')->store('profile_photos', 'public');
        $user->update(['photo' => $photoPath]);

        return response()->json([
            'message' => 'Photo de profil mise à jour avec succès.',
            'photo' => $photoPath,
        ], 200);
    }



    public function publicProfile($id)
    {
        $user = User::with(['niveaux_users.parrainageNiveau'])->findOrFail($id);

        return response()->json([
            'id' => $user->id,
            'nom' => $user->nom,
            'photo' => $user->photo,
            'premium' => $user->premium,
            'subscription_ends_at' => $user->subscription_ends_at,
            'commercant' => $user->commercant ? true : false,
            'ville' => $user->ville,
            'telephone' => $user->telephone,
            'niveaux_users' => $user->niveaux_users,
        ]);
    }

    public function updateProfile(Request $request)
    {

        $user = $request->user();
$user->update($request->all());
      return response()->json([
            'user' => $user->load('commercant')
        ], 200);
  
    }
    // protected function calculateTotalGains($userId)
    // {
    //     $totalParrainagesCommercants = User::where('parrain_id', $userId)->whereHas('commercant')->count();
    //     $niveaux = \App\Models\ParrainageNiveau::orderBy('filleuls_requis')->get();
    //     $totalGains = 0;

    //     foreach ($niveaux as $niveau) {
    //         if ($niveau->filleuls_requis <= $totalParrainagesCommercants) {
    //             $totalGains += $niveau->jetons_bonus;
    //         } else {
    //             break;
    //         }
    //     }

    //     return $totalGains;
    // }
}