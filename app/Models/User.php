<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relationships
    public function transaksiStok(): Relations\HasMany
    {
        return $this->hasMany(TransaksiStok::class);
    }

    public function historiBarang(): Relations\HasMany
    {
        return $this->hasMany(HistoriBarang::class);
    }

    public function messages(): Relations\HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function aktivitasUser(): Relations\HasMany
    {
        return $this->hasMany(AktivitasUser::class);
    }

    public function barangDibuat(): Relations\HasMany
    {
        return $this->hasMany(Barang::class, 'created_by');
    }

    public function conversations(): Relations\BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_members')
                    ->withPivot('last_read_at')
                    ->withTimestamps();
    }

    // Helper methods
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isGudang(): bool
    {
        return $this->role === 'gudang';
    }

    public function canManageMaster(): bool
    {
        return $this->isOwner() || $this->isAdmin();
    }
}