<?php

// app/Http/Controllers/UserController.php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'telephone' => 'required|string|unique:users,telephone',
            'ville' => 'required|string|max:255',
            'mot_de_passe' => 'required|string|min:8',
            'parrain_code' => 'nullable|string|exists:users,code_parrainage',
        ]);

        $user = new User([
            'id' => Uuid::uuid4()->toString(),
            'nom' => $request->nom,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'ville' => $request->ville,
            'mot_de_passe' => Hash::make($request->mot_de_passe),
            'code_parrainage' => Str::random(8), // Génère un code unique
        ]);

        if ($request->parrain_code) {
            $parrain = User::where('code_parrainage', $request->parrain_code)->first();
            $user->parrain_id = $parrain->id;
        }

        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Inscription réussie',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string',
            'mot_de_passe' => 'required|string',
        ]);

        $user = User::where('telephone', $request->telephone)->first();

        if (!$user || !Hash::check($request->mot_de_passe, $user->mot_de_passe)) {
            return response()->json(['message' => 'Identifiants incorrects'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }
}