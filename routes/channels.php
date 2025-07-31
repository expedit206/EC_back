<?php


use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{receiverId}', function ($user, $receiverId) {
    // Autorise l'utilisateur s'il est l'expéditeur ou le destinataire
    return (int) $user->id === (int) $receiverId || $user->id === $receiverId;
});