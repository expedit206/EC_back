<?php

use Illuminate\Support\Facades\Broadcast;

    Broadcast::channel('public-channel', function ($user) {
    // Broadcast::channel('chat.{receiver_id}', function ($user, $receiver_id) {
    // return    (int) $user->id === (int) $receiver_id;
    
    // return (int) $user->id === (int) $id;

    return true;
});
Broadcast::routes();