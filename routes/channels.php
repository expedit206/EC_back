<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{id}', function ($user, $id) {
    $ids = explode('-', $id);
    return in_array($user->id, $ids);
});