<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

// Private user channel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->getAuthIdentifier() === (string) $id;
});

// Private conversation channel
Broadcast::channel('conversations.{conversationId}', function ($user, $conversationId) {
    return Conversation::query()
        ->whereKey($conversationId)
        ->where('participants', (string) $user->getAuthIdentifier())
        ->exists();
});

// Public online channel
Broadcast::channel('online', function ($user) {
    return [
        'id' => (string) $user->getAuthIdentifier(),
        'name' => $user->name,
    ];
});
