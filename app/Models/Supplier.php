<?php
// app/Models/Supplier.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'suppliers';  // ← TAMBAH

    protected $fillable = [
        'nama',
        'kontak',
        'email',
        'alamat',
    ];

    public function barang(): Relations\HasMany
    {
        return $this->hasMany(Barang::class);
    }
}