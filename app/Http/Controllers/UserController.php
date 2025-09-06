<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Message;
use Illuminate\Http\Request;
use App\Models\Collaboration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    // Inscription
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
            'nom' => $request->nom,
            'telephone' => $request->telephone,
            'email' => $request->email,
            'ville' => $request->ville,
            'mot_de_passe' => Hash::make($request->mot_de_passe),
            'premium' => false,
            'parrain_id' => $request->parrain_id,
        ]);

        // Générer un token API pour Bearer Auth
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Inscription réussie',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // Connexion
    // Connexion
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'mot_de_passe' => 'required|string',
        ]);

        $field = filter_var($request->input('login'), FILTER_VALIDATE_EMAIL) ? 'email' : 'telephone';
        $user = User::where($field, $request->input('login'))->first();

        if (!$user || !Hash::check($request->mot_de_passe, $user->mot_de_passe)) {
            throw ValidationException::withMessages([
                'login' => ['Les informations d\'identification sont incorrectes.'],
            ]);
        }

        // ✅ On ne fait pas Auth::login() (cookie)
        // ✅ On crée uniquement un token Sanctum (Bearer)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie',
            'user' => $user,
            'token' => $token,
        ]);     
    }



    // Déconnexion
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie'], 200);
    }


    // Mettre à jour les notifications
    public function updateNotifications(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
        ]);

        $user->update($data);

        return response()->json(['user' => $user]);
    }

    // Compteurs pour badges
    public function badges(Request $request)
    {
        $user = $request->user();
        $commercant = $user->commercant; // Récupérer le commerçant associé à l'utilisateur

        if (!$commercant) {
            return response()->json([
                'collaborations_pending' => 0,
                'unread_messages' => 0,
            ]);
        }

        $collaborationsPendingCount = Collaboration::where(function ($query) use ($commercant) {
            $query->where('commercant_id', $commercant->id) // Collaborations initiées par ce commerçant
                ->orWhereHas('produit.commercant', function ($query) use ($commercant) {
                    $query->where('id', $commercant->id); // Collaborations où ce commerçant est le propriétaire
                });
        })->where('statut', 'en_attente')
            ->count();

        $unreadMessagesCount = Message::where('receiver_id', $user->id)
            ->where('is_read', false)
            ->count();

        $conversations = $user->conversations()->withCount(['messages as unread_count' => function ($query) use ($user) {
            $query->where('receiver_id', $user->id)->where('is_read', false);
        }])->get();

        return response()->json([
            'collaborations_pending' => $collaborationsPendingCount,
            'unread_messages' => $unreadMessagesCount,
            'conversations' => $conversations,
        ]);
    }

    // Récupérer le profil utilisateur
    public function profile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $user->load('commercant', 'niveaux_users.parrainageNiveau');

        return response()->json([
            'user' => $user,
        ]);
    }
}