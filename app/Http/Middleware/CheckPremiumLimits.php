<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPremiumLimits
{
    public function handle(Request $request, Closure $next, $limitType = null)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        switch ($limitType) {
            case 'product':
                $productCount = $user->commercant->produits()->count();
                if (!$user->is_premium && $productCount >= 50) {
                    return response()->json(['message' => 'Limite de 50 produits atteinte. Passez à Premium pour plus.'], 403);
                }
                break;

            case 'collaboration':
                $collaborationCount = $user->collaborations()->count(); // À adapter selon votre modèle
                if (!$user->is_premium && $collaborationCount >= 50) {
                    return response()->json(['message' => 'Limite de 50 collaborations atteinte. Passez à Premium pour plus.'], 403);
                }
                break;

            case 'boost':
                $usedTokens = $user->usedBoostTokens ?? 0; // À implémenter avec une table ou un champ
                $maxTokens = $user->is_premium ? 30 : 0;
                if ($usedTokens >= $maxTokens) {
                    return response()->json(['message' => 'Plus de jetons de boost disponibles ce mois-ci.'], 403);
                }
                break;
        }

        return $next($request);
    }
}