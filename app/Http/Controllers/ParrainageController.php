<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\NiveauParrainage;
use App\Models\ParrainageNiveau;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ParrainageController extends Controller
{
    /**
     * Récupérer les données du tableau de bord de parrainage pour l'utilisateur connecté
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $parrainages = User::where('parrain_id', $user->id)->with('commercant')->get()->map(function ($filleul) {
            return [
                'filleul_nom' => $filleul->nom,
                'date_inscription' => $filleul->created_at,
                'est_commercant' => $filleul->commercant ? true : false,
            ];
        });
        $totalGains = $this->calculateTotalGains($user->id);
        $totalParrainages = $parrainages->count();

        // Récupérer les niveaux et déterminer le niveau actuel
        $niveaux = ParrainageNiveau::orderBy('filleuls_requis')->get();
        $niveauActuel = $niveaux->Where('filleuls_requis', '<=', $totalParrainages)->last() ?? $niveaux->first();
        $niveauSuivant = $niveaux->firstWhere('filleuls_requis', '>', $totalParrainages) ?? $niveaux->last();

        // $progression = $niveauSuivant ;
        // > 0 && $niveauSuivant
        //     ? (($totalParrainages - $niveauActuel->filleuls_requis) / ($niveauSuivant->filleuls_requis - $niveauActuel->filleuls_requis)) * 100
        //     : 0;
        // return response()->json(['message' => 
        // ($totalParrainages - $niveauActuel->filleuls_requis)/
        //   ($niveauSuivant
        //   ->filleuls_requis - $niveauActuel->filleuls_requis)
        // ]);
        $progression = $totalParrainages > 0 && $niveauSuivant
            ? (($totalParrainages - $niveauActuel->filleuls_requis) / ($niveauSuivant->filleuls_requis - $niveauActuel->filleuls_requis)) * 100
            : 0;

        return response()->json([
            'code' => $user->parrainage_code,
            'parrainages' => $parrainages,
            'total_gains' => $totalGains,
            'total_parrainages' => $totalParrainages,
            'niveau_actuel' => [
                'id' => $niveauActuel->id,
                'nom' => $niveauActuel->nom,
                'emoji' => $niveauActuel->emoji,
                'couleur' => $niveauActuel->couleur,
                'filleuls_requis' => $niveauActuel->filleuls_requis,
                'jetons_bonus' => $niveauActuel->jetons_bonus,
                'avantages' => json_decode($niveauActuel->avantages, true),
            ],
            'niveau_suivant' => $niveauSuivant ? [
                'id' => $niveauSuivant->id,
                'nom' => $niveauSuivant->nom,
                'filleuls_requis' => $niveauSuivant->filleuls_requis,
            ] : null,
            'progression' => min($progression, 100), // Limiter à 100%
        ]);
    }


    public function getAllNiveaux()
    {
        $niveaux = ParrainageNiveau::orderBy('filleuls_requis')->get()->map(function ($niveau) {
            return [
                'id' => $niveau->id,
                'nom' => $niveau->nom,
                'emoji' => $niveau->emoji,
                'filleuls_requis' => $niveau->filleuls_requis,
                'jetons_bonus' => $niveau->jetons_bonus,
                'avantages' => json_decode($niveau->avantages, true),
            ];
        });

        return response()->json(['niveaux' => $niveaux]);
    }
    /**
     * Générer une suggestion de code de parrainage
     */
    public function generateCode(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $suggestedCode = Str::random(3) . '' . Str::slug($user->nom);
        $suggestedCode = strtoupper(substr($suggestedCode, 0, 6));

        while (User::where('parrainage_code', $suggestedCode)->exists()) {
            $suggestedCode = Str::slug($user->nom) . '-' . Str::random(4);
            $suggestedCode = strtoupper(substr($suggestedCode, 0, 10));
        }

        return response()->json(['suggested_code' => $suggestedCode]);
    }

    /**
     * Créer ou mettre à jour un code de parrainage personnalisé
     */
    public function createCode(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $request->validate([
            'code' => 'required|string|max:10|unique:users,parrainage_code',
        ]);

        $customCode = strtoupper($request->code);

        if (User::where('parrainage_code', $customCode)->where('id', '!=', $user->id)->exists()) {
            return response()->json(['message' => 'Ce code est déjà utilisé'], 400);
        }

        $user->update(['parrainage_code' => $customCode]);
        $link = url('/invite/' . $customCode);

        return response()->json([
            'message' => 'Code de parrainage créé avec succès',
            'code' => $customCode,
            'link' => $link,
        ]);
    }

    /**
     * Calculer les gains totaux de l'utilisateur (1 jeton de 500 FCFA par commerçant actif)
     */
    protected function calculateTotalGains($userId)
    {
        $commercantsParraines = User::where('parrain_id', $userId)->whereHas('commercant')->get();
        return $commercantsParraines->count(); // 1 jeton (500 FCFA) par commerçant actif
    }
}
