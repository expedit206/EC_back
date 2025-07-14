<?php

// app/Http/Controllers/ParrainageController.php
namespace App\Http\Controllers;

use App\Models\Parrainage;
use App\Models\User;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class ParrainageController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|exists:users,code_parrainage',
        ]);

        $parrain = User::where('code_parrainage', $request->code)->first();
        $filleul = auth()->user();

        if ($parrain->id === $filleul->id) {
            return response()->json(['message' => 'Vous ne pouvez pas vous parrainer vous-même'], 400);
        }

        // Vérifier si le filleul a déjà un parrain
        if (Parrainage::where('filleul_id', $filleul->id)->exists()) {
            return response()->json(['message' => 'Vous avez déjà un parrain'], 400);
        }

        $parrainage = new Parrainage([
            'id' => Uuid::uuid4()->toString(),
            'parrain_id' => $parrain->id,
            'filleul_id' => $filleul->id,
            'niveau' => 1,
            'recompense' => 500, // 500 FCFA comme bonus initial
        ]);
        $parrainage->save();

        // Ajouter une récompense au parrain
        $parrain->solde += 500;
        $parrain->save();

        // Vérifier si le filleul est un commerçant actif (au moins 1 produit)
        $commercant = $filleul->commercant;
        if ($commercant && $commercant->boutiques()->has('produits')->exists()) {
            $this->updateParrainLevel($parrain);
        }

        return response()->json(['message' => 'Parrainage enregistré', 'parrainage' => $parrainage], 201);
    }

    protected function updateParrainLevel(User $parrain)
    {
        $filleulsActifs = Parrainage::where('parrain_id', $parrain->id)
            ->whereHas('filleul.commercant.boutiques.produits')
            ->count();

        $niveaux = [
            ['nom' => 'Découvreur', 'requis' => 1, 'recompense' => 500],
            ['nom' => 'Prometteur', 'requis' => 3, 'recompense' => 1500],
            ['nom' => 'Relais Local', 'requis' => 5, 'recompense' => 3000],
            ['nom' => 'Influenceur Marché', 'requis' => 7, 'recompense' => 5000],
            ['nom' => 'Ambassadeur Bronze', 'requis' => 10, 'recompense' => 10000],
            ['nom' => 'Ambassadeur Argent', 'requis' => 15, 'recompense' => 15000],
            ['nom' => 'Ambassadeur Or', 'requis' => 25, 'recompense' => 25000],
            ['nom' => 'Légende', 'requis' => 40, 'recompense' => 40000],
            ['nom' => 'Grand Mentor', 'requis' => 60, 'recompense' => 75000],
        ];

        foreach ($niveaux as $niveau) {
            if ($filleulsActifs >= $niveau['requis']) {
                $parrain->solde += $niveau['recompense'];
                $parrain->save();
                Parrainage::where('parrain_id', $parrain->id)->update(['niveau' => $niveau['requis']]);
            }
        }
    }
}