<?php
// app/Models/TransaksiStok.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class TransaksiStok extends Model
{
    use HasFactory;

    protected $table = 'transaksi_stok';
    
    protected $fillable = [
        'barang_id',
        'user_id',
        'jenis',
        'jumlah',
        'stok_sebelum',
        'stok_sesudah',
        'keterangan',
        'referensi',
    ];

    protected $casts = [
        'jumlah' => 'integer',
        'stok_sebelum' => 'integer',
        'stok_sesudah' => 'integer',
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