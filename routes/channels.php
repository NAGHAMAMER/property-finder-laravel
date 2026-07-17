<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Standard private channel used by realtime notifications and chat messages.
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return (int) $user->id === $id;
});

// Friendly alias kept for mobile clients that prefer a shorter channel name.
Broadcast::channel('user.{id}', function (User $user, int $id): bool {
    return (int) $user->id === $id;
});
