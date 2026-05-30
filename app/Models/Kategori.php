<?php
// app/Models/Kategori.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Kategori extends Model
{
    use HasFactory;

    protected $table = 'kategori';
    
    protected $fillable = [
        'nama',
        'deskripsi',
    ];

    public function barang(): Relations\HasMany
    {
        return $this->hasMany(Barang::class);
    }
}