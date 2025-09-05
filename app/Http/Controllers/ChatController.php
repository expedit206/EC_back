<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Message;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

class ChatController extends Controller
{
    /**
     * Récupérer la liste des conversations de l'utilisateur connecté
     */
    public function conversations(Request $request)
    {
        // return response()->json(['conversations' => '$conversations']);

        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $conversations = Message::where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->selectRaw('LEAST(sender_id, receiver_id) as user1, GREATEST(sender_id, receiver_id) as user2')
            ->groupBy('user1', 'user2')
            ->get()
            ->map(function ($message) use ($user) {
                $otherUserId = $message->user1 == $user->id ? $message->user2 : $message->user1;
                $otherUser = User::find($otherUserId);

                // Récupérer le dernier message de la conversation
                $lastMessage = Message::where(function ($q) use ($user, $otherUserId) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $otherUserId);
                })->orWhere(function ($q) use ($user, $otherUserId) {
                    $q->where('sender_id', $otherUserId)->where('receiver_id', $user->id);
                })->latest()->first();

                return [
                    'user_id' => $otherUserId,
                    'name' => $otherUser ? $otherUser->nom : 'Inconnu',
                    'last_message' => $lastMessage->content ?? '',
                    'updated_at' => $lastMessage->updated_at ?? now(),
                'updated_at' => $lastMessage->updated_at ?? now(),

            ];
            });

        return response()->json(['conversations' => $conversations]);
    }

    /**
     * Récupérer les messages d'une conversation spécifique
     */
    public function index($receiverId, Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $offset = $request->query('offset', 0); // Par défaut, commence à 0
        $limit = 30; // Limite de 30 messages par requête

        $messages = Message::where(function ($query) use ($user, $receiverId) {
            $query->where('sender_id', $user->id)->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($user, $receiverId) {
            $query->where('sender_id', $receiverId)->where('receiver_id', $user->id);
        })
            ->with('sender', 'receiver', 'product')
            ->orderBy('id', 'desc') // Tri décroissant pour les derniers messages en premier
            ->offset($offset)
            ->limit($limit)
            ->get();
            // ->reverse(); // Inverse pour avoir les plus anciens en haut

        return response()->json(['messages' => $messages, 'hasMore' => $messages->count() === $limit]);
    }

    /**
     * Envoyer un nouveau message
     */
    public function store(Request $request, $receiverId)
    {
        $user= $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }
        
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'product_id' => 'nullable|exists:produits,id',
        ]);
        
        // \Log::info("Événement MessageSent déclenché", ['message' => $message]);
        
        $message = new Message();
        $message->sender_id = $user->id;
        $message->receiver_id = $receiverId;
        $message->content = $validated['content'];
        $message->product_id = $validated['product_id']??null;
        $message->save();

        try {
            broadcast(new MessageSent($message));
        } catch (\Exception $e) {
            \Log::warning('Broadcast échoué : ' . $e->getMessage());
        }

        // broadcast(new MessageSent($message));
        
        // return response()->json(['message' => 'Message envoyé avec succès', 'message_data' => $message], 201);
        // Broadcast(new MessageSent($message->load('sender', 'receiver')));
        // event(new MessageSent   ("Hello depuis Laravel 🚀"));

        
        
//         return response()->json(['message' => 'Message envoyé avec succès',
//          'message_data' =>  $validated,
// ], 201);
        return response()->json(['message' => 'Message envoyé avec succès',
         'message_data' =>   \Auth::check(),
], 201);
    }

    
    public function markAllAsRead(Request $request)
    {
       
        $user = $request->user();
        Message::where('receiver_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
            
        $unreadMessagesCount = Message::where('receiver_id', $user->id)->where('is_read', false)->count();
        return response()->json(['message' => 'Tous les messages marqués comme lus', 'unread_messages' => $unreadMessagesCount]);
    }
}