<?php

// app/Http/Controllers/BoutiqueController.php
namespace App\Http\Controllers;

use App\Models\Boutique;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class BoutiqueController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
            'ville' => 'required|string|max:255',
        ]);

        $user = auth()->user();
        $commercant = $user->commercant;

        if (!$commercant) {
            return response()->json(['message' => 'Vous devez d\'abord créer un compte commerçant'], 403);
        }

        $boutique = new Boutique([
            // 'id' => Uuid::uuid4()->toString(),
            // 'commercant_id' => $commercant->id,
            // 'nom' => $request->nomkeyboard: nom
            // 'description' => $request->description,
            // 'logo' => $request->logo,
            // 'ville' => $request->ville,
            // 'actif' => true,
        ]);
        $boutique->save();

        return response()->json(['message' => 'Boutique créée', 'boutique' => $boutique], 201);
    }

    public function index()
    {
        $boutiques = Boutique::with('commercant', 'produits')->get();
        return response()->json(['boutiques' => $boutiques]);
    }
}