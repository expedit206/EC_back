<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Parrainage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Collaboration;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function register(Request $request)
    {
        // return response()->json(['request' => $request->all()]);
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
            'token_expires_at' => Carbon::now()->addDays(7),
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
        $user->update([
            'token' => $token,
            'token_expires_at' => Carbon::now()->addDays(7), // Expire dans 7 jours
        ]);
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
            'token' => $token,
        ], 200);
    }

    public function profile(Request $request)
    {

        // $token = $request->header('Authorization');

        $user = $request->user;
        // $user = User::where('token', $token)->first();
        $user->load('commercant');
        return response()->json(
            [
                'user' => $user,
            ],
        );
    }

    public function updateProfilePhoto(Request $request)
    {
        $user = $request->user;

        $request->validate([
            'photo' => 'required|image|max:2048', // Limite à 2 Mo et accepte uniquement les images
        ]);

        // Supprimer l'ancienne photo si elle existe
        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }

        // Stocker la nouvelle photo
        $photoPath = $request->file('photo')->store('profile_photos', 'public');
        $user->update(['photo' => $photoPath]);

        return response()->json([
            'message' => 'Photo de profil mise à jour avec succès.',
            'photo' => $photoPath,
        ], 200);
    }

    public function logout()
    {
        return response()->json(['message' => 'Déconnexion réussie'], 200);
    }

    public function updateNotifications(Request $request)
    {
        $user = $request->user;
        $data = $request->validate([
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
        ]);

        $user->update($data);
        $user->load('commercant');

        return response()->json(['user' => $user]);
    }
    //update profile photo

    public function badges(Request $request)
    {
        $user = $request->user;

        $collaborations_pending = Collaboration::where('user_id', $user->id)
            ->where('statut', 'en_attente')
            ->get();




        return response()->json([
            'collaborations_pending' => $collaborations_pending,
        ]);
    }
}