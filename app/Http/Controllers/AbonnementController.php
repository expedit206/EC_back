<?php

// app/Http/Controllers/AbonnementController.php
namespace App\Http/Controllers;

use App\Models\Abonnement;
use App\Models\User;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Carbon\Carbon;

class AbonnementController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'plan' => 'required|string|in:premium_pro,premium_max',
        ]);

        $user = auth()->user();
        $duree = $request->plan === 'premium_pro' ? 30 : 365; // 30 jours pour Pro, 1 an pour Max

        $abonnement = new Abonnement([
            'id' => Uuid::uuid4()->toString(),
            'user_id' => $user->id,
            'plan' => $request->plan,
            'debut' => Carbon::now(),
            'fin' => Carbon::now()->addDays($duree),
            'actif' => true,
        ]);
        $abonnement->save();

        $user->premium = true;
        $user->save();

        return response()->json(['message' => 'Abonnement créé', 'abonnement' => $abonnement], 201);
    }
}