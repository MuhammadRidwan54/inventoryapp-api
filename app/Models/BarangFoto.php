<?php
// app/Models/BarangFoto.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class BarangFoto extends Model
{
    use HasFactory;

    protected $table = 'barang_fotos';
    
    protected $fillable = [
        'barang_id',
        'url',
        'public_id',
        'is_primary',
        'urutan',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'urutan' => 'integer',
    ];

    public function barang(): Relations\BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }
}