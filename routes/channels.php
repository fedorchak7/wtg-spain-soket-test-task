<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

Broadcast::channel('messages.{id}', function (User $user, int $id) {
    return $user->id === $id;
});

Broadcast::channel('online', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});
