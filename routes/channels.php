<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

// Broadcast::channel('chat.4', function ($user, $id) {
//     // $ids = explode('-', $id);
//     // return in_array($user->id, $ids);

//     return true;
// });
Broadcast::channel('chat', function ($user) {
    // return true; // Autoriser tout utilisateur authentifié pour le test
    // return Auth::check(); // Autoriser tout utilisateur authentifié pour le test
    return Auth::guard('api')->check(); // Use 'api' guard for Sanctum token
}, ['guards' => 'api']);
// Broadcast::channel('chat', function ($user) {
//     // Autoriser l'accès si l'utilisateur est l'expéditeur ou le destinataire
//     return in_array($user->id, [$this->message->sender_id, $this->message->receiver_id]);
// });