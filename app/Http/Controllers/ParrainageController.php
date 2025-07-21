<?php

namespace App\Http\Controllers;

use App\Models\Parrainage;
use App\Models\User;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ParrainageController extends Controller
{
    public function generateCodeSuggestion(Request $request)
    {
        $user = $request->user;
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non connecté'], 401);
        }

        $suggestion = $this->generateUniqueCodeSuggestion($user);
        return response()->json(['suggested_code' => $suggestion]);
    }

    // Créer et enregistrer le code après validation
    public function createCode(Request $request)
    {
        $user = $request->user;
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non connecté'], 401);
        }

        $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $code = Str::upper($request->code);

        // Vérifier si le code existe déjà
        if (User::where('parrainage_code', $code)->whereNotNull('parrainage_code')->exists()) {
            return response()->json(['message' => 'Ce code est déjà pris'], 400);
        }

        // Vérifier si l'utilisateur a déjà un code
        if ($user->parrainage_code) {
            return response()->json(['message' => 'Vous avez déjà un code de parrainage actif'], 400);
        }

        $user->update(['parrainage_code' => $code]);
        return response()->json([
            'message' => 'Code de parrainage créé avec succès',
            'code' => $code,
            'link' => "https://espacecameroun.cm/invite/{$code}"
        ]);
    }

    // Méthode privée pour générer un code unique
    private function generateUniqueCodeSuggestion($user)
    {
        $baseCode = Str::upper(Str::random(4)  . date('s'));
        $code = $baseCode;

        $counter = 1;
        while (User::where('parrainage_code', $code)->whereNotNull('parrainage_code')->exists()) {
            $code = "{$baseCode}{$counter}";
            $counter++;
            if ($counter > 100) {
                throw new \Exception('Impossible de générer un code unique');
            }
        }

        return $code;
    }

    public function registerParrainage(Request $request)
    {
        $request->validate([
            'code' => 'required|string|exists:users,parrainage_code',
        ]);

        $user = $request->user;
        if (!$user || $user->commercant) {
            return response()->json(['message' => 'Inscription invalide'], 400);
        }

        $parrain = User::where('parrainage_code', $request->code)->first();
        if (!$parrain) {
            return response()->json(['message' => 'Code invalide'], 400);
        }

        $existingParrainage = Parrainage::where('parrain_id', $parrain->id)->where('filleul_id', $user->id)->first();
        if ($existingParrainage) {
            return response()->json(['message' => 'Ce parrainage existe déjà'], 400);
        }

        if ($parrain->id === $user->id) {
            return response()->json(['message' => 'Vous ne pouvez pas vous parrainer vous-même'], 400);
        }

        Parrainage::create([
            'parrain_id' => $parrain->id,
            'filleul_id' => $user->id,
            'statut' => 'pending',
        ]);

        return response()->json(['message' => 'Parrainage enregistré avec succès']);
    }

    public function validateParrainage($userId)
    {
        $user = User::find($userId);
        if (!$user || !$user->commercant) {
            return response()->json(['message' => 'Utilisateur invalide'], 400);
        }

        $parrainage = Parrainage::where('filleul_id', $userId)->first();
        if (!$parrainage || $parrainage->statut !== 'pending') {
            return response()->json(['message' => 'Parrainage non éligible'], 400);
        }

        $hasProduct = Produit::where('commercant_id', $user->commercant->id)->exists();
        if (!$hasProduct) {
            return response()->json(['message' => 'Publiez un produit pour valider le parrainage'], 400);
        }

        $parrainage->update([
            'statut' => 'active',
            'date_activation' => now(),
            'gains' => $parrainage->gains + 500,
        ]);

        return response()->json(['message' => 'Parrainage validé, bonus ajouté']);
    }

    public function getParrainageDashboard(Request $request)
    {
        $user = $request->user;
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non connecté'], 401);
        }

        $filleuls = User::where('parrain_id', $user->id)->get();
        $totalGains = $filleuls->sum(function ($filleul) {
            return $filleul->commercant && Produit::where('commercant_id', $filleul->commercant->id)->exists() ? 500 : 0;
        });

        return response()->json([
            'code' => $user->parrainage_code ?? $user->generateParrainageCode(),
            'parrainages' => $filleuls->map(function ($filleul) {
                return [
                    'filleul_nom' => $filleul->nom,
                    'date_inscription' => $filleul->created_at,
                    'est_commercant' => $filleul->commercant ?? false,
                    'ventes_generees' => $filleul->commercant ? Produit::where('commercant_id', $filleul->commercant->id)->sum('prix') ?? 0 : 0,
                ];
            }),
            'total_gains' => $totalGains,
        ]);
    }
}