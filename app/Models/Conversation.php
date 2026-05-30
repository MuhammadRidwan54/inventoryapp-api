<?php
// app/Models/Conversation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Conversation extends Model
{
    use HasFactory;

    protected $table = 'conversations';  // ← TAMBAH

    protected $fillable = [
        'jenis',
        'judul',
    ];

    protected $casts = [
        'jenis' => 'string',
    ];

    public function messages(): Relations\HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function members(): Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_members')
                    ->withPivot('last_read_at')
                    ->withTimestamps();
    }

    public function lastMessage(): ?Message
    {
        return $this->messages()->latest()->first();
    }

    public function unreadCount(User $user): int
    {
        $lastRead = $this->members()->where('user_id', $user->id)->first()?->pivot->last_read_at;
        
        $query = $this->messages();
        
        if ($lastRead) {
            $query->where('created_at', '>', $lastRead);
        }
        
        return $query->count();
    }
}