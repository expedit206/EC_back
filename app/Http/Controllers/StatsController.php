<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Calcul du nombre de parrainages actifs (filleuls commerçants)
        $parrainages = User::where('parrain_id', $user->id)
            ->whereHas('commercant')
            ->count();

        // Revenus basés sur les jetons attribués via le parrainage (exemple)
        $revenus = $user->jetons * 10; // Hypothèse : 10 FCFA par jeton (à ajuster selon vos besoins)

        // Dernière connexion
        $last_login = $user->last_login ?? 'Non disponible';

        return response()->json([
            'parrainages' => $parrainages,
            'revenus' => $revenus,
            'last_login' => $last_login,
        ]);
    }
}