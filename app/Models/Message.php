<?php
// app/Models/Message.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Message extends Model
{
    use HasFactory;

    protected $table = 'messages';  // ← TAMBAH

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'is_system',
        'body',
        'meta',
        'read_at',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'meta' => 'array',
        'read_at' => 'datetime',
    ];

    public function conversation(): Relations\BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }
}