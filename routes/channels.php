<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('users.{id}', fn ($user, $id) => (int) $user->id === (int) $id);
Broadcast::channel('system.notifications', fn ($user) => (bool) $user->active);
