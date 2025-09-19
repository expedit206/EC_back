<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.4', function ($user, $id) {
    // $ids = explode('-', $id);
    // return in_array($user->id, $ids);

    return true;
});