<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{productId}.{receiverId}', function ($user, $productId, $receiverId) {
return (int) $user->id === (int) $receiverId || $user->parrain_id === $receiverId;
});