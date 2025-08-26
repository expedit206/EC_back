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
            ->select('sender_id', 'receiver_id')
            ->groupBy('sender_id', 'receiver_id')
            ->get()
            ->map(function ($message) use ($user) {
                $otherUserId = $message->sender_id === $user->id ? $message->receiver_id : $message->sender_id;
                $otherUser = User::find($otherUserId);
                return [
                    'user_id' => $otherUserId,
                    'name' => $otherUser ? $otherUser->nom : 'Inconnu',
                    'last_message' => $message->content,
                    'updated_at' => $message->updated_at,
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
            ->orderBy('id', 'asc') // Tri décroissant pour les derniers messages en premier
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
        $user =$request->user();
        
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
                // broadcast(new MessageSent($message));
                
                // return response()->json(['message' => 'Message envoyé avec succès', 'message_data' => $message], 201);
        Broadcast(new MessageSent($message));
        // event(new MessageSent   ("Hello depuis Laravel 🚀"));

        // ->toOthers();
        // return response()->json(['message' => event(new MessageSent($message))]);
        // \Log::info('Broadcast auth request', [
        //     'USER' => $request->user(),
        //     'all_request' => $request->all(),
        //     'headers' => $request->headers->all(),
        //     'cookies' => $request->cookies->all(),
        //     'session_id' => $request->session()->getId(),
        //     'user_authenticated' => Auth::check(),
        //     'user_id' => Auth::id(),
        //     // 'broad' => Broadcast::auth(
        //     // $request),
        // ]);
        return response()->json(['message' => 'Message envoyé avec succès',
         'message_data' =>   \Auth::check(),
        //  'message_data' =>  event(new MessageSent($message))
         
        //  'message_data' =>  Broadcast::auth($request)
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