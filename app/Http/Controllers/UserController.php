<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Parrainage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'telephone' => 'required|string|max:20|unique:users,telephone',
            'email' => 'nullable|email|max:255|unique:users,email',
            'ville' => 'required|string|max:255',
            'mot_de_passe' => 'required|string|min:8',
            'parrain_id' => 'nullable|exists:users,id',
        ]);

        $user = User::create([
            // 'id' => Str::uuid(),
            'nom' => $request->nom,
            'telephone' => $request->telephone,
            'email' => $request->email,
            'ville' => $request->ville,
            'mot_de_passe' => Hash::make($request->mot_de_passe),
            // 'role' => 'user',
            'premium' => false,
            'parrain_id' => $request->parrain_id,
            'token' => Str::uuid(), // Générer un token à l'inscription
        ]);


        if ($request->parrain_id) {
            Parrainage::create([
                // 'id' => Str::uuid(),
                'parrain_id' => $request->parrain_id,
                'filleul_id' => $user->id,
                'niveau' => 1,
                'recompense' => 500,
            ]);
        }

        return response()->json([
            'message' => 'Inscription réussie',
            'user' => [
                // 'id' => $user->id,
                'nom' => $user->nom,
                'email' => $user->email,
                'telephone' => $user->telephone,
                'ville' => $user->ville,
                'role' => $user->role,
                'premium' => $user->premium,
                'parrain_id' => $user->parrain_id,
            ],
            'token' => $user->token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'mot_de_passe' => 'required|string',
        ]);
        $field = filter_var($request->input('login'), FILTER_VALIDATE_EMAIL) ? 'email' : 'telephone';
        $user = User::where($field, $request->input('login'))->first();

        if (!$user || !Hash::check($request->input('mot_de_passe'), $user->mot_de_passe)) {
            throw ValidationException::withMessages([
                'login' => ['Les informations d\'identification sont incorrectes.'],
            ]);

        }
        $token = Str::uuid();
        $user->update(['token' => $token]);
        $user->load('commercant');
        return response()->json([
            'message' => 'Connexion réussie',
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'email' => $user->email,
                'telephone' => $user->telephone,
                'ville' => $user->ville,
                'role' => $user->role,
                'premium' => $user->premium,
            ],
        ], 200);
    }

    public function profile(Request $request)
    {

        return response()->json([
            'user' => $request->input('user'),
        ], 200);
    }

    public function logout()
    {
        return response()->json(['message' => 'Déconnexion réussie'], 200);
    }
}