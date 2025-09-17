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
                if (!$user->premium && $productCount >= 50) {
                    return response()->json(['message' => 'Limite de 50 produits atteinte. Passez à Premium pour plus.'], 403);
                }
                break;

            case 'collaboration':
                $collaborationCount = $user->collaborations()->count(); // À adapter selon votre modèle
                if (!$user->premium && $collaborationCount >= 50) {
                    return response()->json(['message' => 'Limite de 50 collaborations atteinte. Passez à Premium pour plus.'], 403);
                }
                break;

        
        }

        return $next($request);
    }
}