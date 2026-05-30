<?php
// app/Events/NewMessageEvent.php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $conversationId;

    public function __construct(Message $message, $conversationId)
    {
        $this->message = $message;
        $this->conversationId = $conversationId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('conversation.' . $this->conversationId);
    }

    public function broadcastAs()
    {
        return 'new-message';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->conversationId,
            'body' => $this->message->body,
            'is_system' => $this->message->is_system,
            'sender' => $this->message->sender ? [
                'id' => $this->message->sender->id,
                'name' => $this->message->sender->name,
                'role' => $this->message->sender->role,
            ] : null,
            'created_at' => $this->message->created_at->toISOString(),
        ];
    }
}