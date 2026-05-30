<?php
// app/Models/HistoriBarang.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class HistoriBarang extends Model
{
    use HasFactory;

    protected $table = 'histori_barang';
    
    protected $fillable = [
        'barang_id',
        'user_id',
        'field',
        'old_value',
        'new_value',
        'aksi',
        'ip_address',
    ];

    public function barang(): Relations\BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }

    public function user(): Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}