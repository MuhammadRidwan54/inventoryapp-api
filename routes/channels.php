<?php
// routes/channels.php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Private channel untuk conversation
Broadcast::channel('conversation.{conversationId}', function (User $user, $conversationId) {
    return \App\Models\Conversation::where('id', $conversationId)
        ->whereHas('members', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->exists();
});

// Private channel untuk stok updates (owner & admin only)
Broadcast::channel('stok-updates', function (User $user) {
    return in_array($user->role, ['owner', 'admin']);
});

// Private channel untuk stok alerts (owner & admin only)
Broadcast::channel('stok-alerts', function (User $user) {
    return in_array($user->role, ['owner', 'admin']);
});

// Private channel per user
Broadcast::channel('user.{userId}', function (User $user, $userId) {
    return (int) $user->id === (int) $userId;
});