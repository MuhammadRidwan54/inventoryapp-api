<?php
// app/Events/UnreadCountUpdatedEvent.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UnreadCountUpdatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $unreadCount;

    public function __construct($userId, $unreadCount)
    {
        $this->userId = $userId;
        $this->unreadCount = $unreadCount;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'unread-count-updated';
    }

    public function broadcastWith()
    {
        return [
            'unread_count' => $this->unreadCount,
        ];
    }
}