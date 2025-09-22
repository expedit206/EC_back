<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Collaboration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    // Inscription (existant, inchangé)
    public function register(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'telephone' => 'required|string|max:20|unique:users,telephone',
            'mot_de_passe' => 'required|string|min:6|confirmed',
            'parrain_code' => 'nullable|string|max:50|exists:users,parrainage_code',
        ]);

        $parrainId = null;
        if ($request->parrain_code) {
            $parrain = User::where('parrainage_code', $request->parrain_code)->first();
            $parrainId = $parrain ? $parrain->id : null;
        }

        $user = User::create([
            'nom' => $request->nom,
            'telephone' => $request->telephone,
            'mot_de_passe' => Hash::make($request->mot_de_passe),
            'premium' => false,
            'parrain_id' => $parrainId,
        ]);

        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Inscription réussie',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // Connexion (existant, inchangé)
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

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie',
            'user' => $user->load('commercant', 'niveaux_users.parrainageNiveau'),
            'token' => $token,
        ]);
    }

    // Redirection vers Google
    public function redirectToGoogle(Request $request)
    {
        $driver = Socialite::driver('google')->stateless();

        // Build state with action and parrain_code
        $state = [];
        if ($request->query('action')) {
            $state['action'] = $request->query('action'); // e.g., 'register' or 'login'
        }
        if ($request->query('parrain_code')) {
            $state['parrain_code'] = $request->query('parrain_code'); // e.g., '9SKDOM'
        }

        // Get the redirect URL from Socialite
        $redirectUrl = $driver->redirect()->getTargetUrl();

        // Append state and prompt parameters
        if (!empty($state)) {
            $redirectUrl .= strpos($redirectUrl, '?') !== false ? '&' : '?';
            $redirectUrl .= 'state=' . urlencode(http_build_query($state));
        }
        $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'prompt=select_account';

        return redirect()->away($redirectUrl);
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Extract action and parrain_code from state
            $action = 'login'; // Default
            $parrainCode = null;
            if ($request->state) {
                parse_str(urldecode($request->state), $stateParams);
                // return response()->json([
                //     'state' => $request->state,
                //     'action' => $stateParams['action']
                // ], 200);

                $action = $stateParams['action'] ?? 'login';
                $parrainCode = $stateParams['parrain_code'] ?? null;
            }

            // Debug: Check state parsing
            // dd($request->state, $stateParams);

            // Validate parrain_code if provided
            if ($parrainCode) {
                $request->validate([
                    'parrain_code' => 'string|max:50|exists:users,parrainage_code',
                ], ['parrain_code' => $parrainCode]);
            }

            $parrainId = null;
            if ($parrainCode) {
                $parrain = User::where('parrainage_code', $parrainCode)->first();
                $parrainId = $parrain ? $parrain->id : null;
            }

            // Find user
            $user = User::where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)
                ->first();

            if ($action === 'register') {
                // Registration mode: prevent creation if user exists
                if ($user) {
                    $frontendUrl = env('FRONTEND_URL', 'http://localhost:4000') . '/login';
                    return redirect()->away($frontendUrl . '?error=' . urlencode('Compte déjà existant. Veuillez vous connecter.'));
                }

                // Create new user
                $user = User::create([
                    'nom' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'telephone' => null,
                    'mot_de_passe' => Hash::make(Str::random(16)),
                    'premium' => false,
                    'parrain_id' => $parrainId,
                ]);
            } else {
                // Login mode: redirect if no user
                if (!$user) {
                    $frontendUrl = env('FRONTEND_URL', 'http://localhost:4000') . '/register';
                    return redirect()->away($frontendUrl . '?error=' . urlencode('Aucun compte trouvé. Veuillez vous inscrire.') . ($parrainCode ? '&parrain_code=' . urlencode($parrainCode) : ''));
                } elseif (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->id, 'parrain_id' => $parrainId]);
                }
            }

            // Generate Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Redirect to frontend
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:4000') . '/auth/google/callback';
            return redirect()->away($frontendUrl . '?token=' . urlencode($token) . '&user=' . urlencode(json_encode($user->load('commercant', 'niveaux_users.parrainageNiveau'))));
        } catch (\Exception $e) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:4000') . '/' . ($action === 'register' ? 'register' : 'login');
            return redirect()->away($frontendUrl . '?error=' . urlencode('Erreur lors de la ' . ($action === 'register' ? 'inscription' : 'connexion') . ' avec Google: ' . $e->getMessage()));
        }
    }
    // Déconnexion (existant, inchangé)
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie'], 200);
    }

    // Mettre à jour les notifications (existant, inchangé)
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

    // Compteurs pour badges (existant, inchangé)
    public function badges(Request $request)
    {
        $user = $request->user();
        $commercant = $user->commercant;
        if (!$commercant) {
            return response()->json([
                'collaborations_pending' => 0,
                'unread_messages' => 0,
            ]);
        }
        $collaborationsPendingCount = Collaboration::where(function ($query) use ($commercant) {
            $query->where('commercant_id', $commercant->id)
                ->orWhereHas('produit.commercant', function ($query) use ($commercant) {
                    $query->where('id', $commercant->id);
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

    // Récupérer le profil utilisateur (existant, inchangé)
    public function profile(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }
        $user->load('commercant', 'niveaux_users.parrainageNiveau');
        return response()->json(['user' => $user]);
    }
}