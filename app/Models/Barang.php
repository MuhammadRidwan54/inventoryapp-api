<?php
// app/Models/Barang.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Support\Facades\DB;
use App\Models\User;      // ← TAMBAHKAN
use App\Models\Conversation; // ← TAMBAHKAN
use App\Models\Message;  // ← TAMBAHKAN

/**
 * @property int $id
 * @property string $nama
 * @property string $sku
 * @property string|null $deskripsi
 * @property int $stok
 * @property int $stok_minimal
 * @property string $satuan
 * @property int $kategori_id
 * @property int|null $supplier_id
 * @property int $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|Barang newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Barang newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Barang query()
 * @method static \Illuminate\Database\Eloquent\Builder|Barang whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Barang whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Barang whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Barang whereStok($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Barang whereCreatedAt($value)
 * @method bool delete()
 */

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barang';  // ← TAMBAHKAN INI

    protected $fillable = [
        'nama',
        'sku',
        'deskripsi',
        'stok',
        'stok_minimal',
        'satuan',
        'kategori_id',
        'supplier_id',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'stok'       => 'integer',
        'stok_minimal' => 'integer',
        'is_active'  => 'boolean',
    ];

    // Scope: hanya barang aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope: hanya barang nonaktif
    public function scopeNonaktif($query)
    {
        return $query->where('is_active', false);
    }

    // Relationships
    public function kategori(): Relations\BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    public function supplier(): Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy(): Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fotos(): Relations\HasMany
    {
        return $this->hasMany(BarangFoto::class);
    }

    public function transaksiStok(): Relations\HasMany
    {
        return $this->hasMany(TransaksiStok::class);
    }

    public function historiBarang(): Relations\HasMany
    {
        return $this->hasMany(HistoriBarang::class);
    }

    // Helper methods
    public function fotoUtama(): ?BarangFoto
    {
        return $this->fotos()->where('is_primary', true)->first();
    }

    public function isStokMenipis(): bool
    {
        return $this->stok <= $this->stok_minimal;
    }

    public function updateStok(int $newStok, User $user, string $jenis, string $keterangan = null): void
    {
        $oldStok = $this->stok;
        
        DB::transaction(function () use ($newStok, $user, $jenis, $oldStok, $keterangan) {
            // 1. Insert transaksi_stok
            TransaksiStok::create([
                'barang_id' => $this->id,
                'user_id' => $user->id,
                'jenis' => $jenis,
                'jumlah' => abs($newStok - $oldStok),
                'stok_sebelum' => $oldStok,
                'stok_sesudah' => $newStok,
                'keterangan' => $keterangan,
            ]);
            
            // 2. Update stok barang
            $this->update(['stok' => $newStok]);
            
            // 3. Insert histori_barang
            HistoriBarang::create([
                'barang_id' => $this->id,
                'user_id' => $user->id,
                'field' => 'stok',
                'old_value' => $oldStok,
                'new_value' => $newStok,
                'aksi' => 'update',
            ]);
            
            // 4. System message untuk owner
            $this->sendSystemMessageStokBerubah($user, $oldStok, $newStok);
        });
    }
    
    protected function sendSystemMessageStokBerubah(User $user, int $oldStok, int $newStok): void
    {
        $perubahan = $newStok > $oldStok ? 'menambah' : 'mengurangi';
        $selisih = abs($newStok - $oldStok);
        
        $message = "{$user->name} ({$user->role}) {$perubahan} stok {$this->nama} sebanyak {$selisih} {$this->satuan}. Stok sekarang: {$newStok}";
        
        // Cari owner pakai DB::table (aman dari error IDE)
        $owner = DB::table('users')->where('role', 'owner')->first();
        
        if ($owner) {
            $conversation = Conversation::firstOrCreate(
                ['jenis' => 'system'],
                ['judul' => 'System Notifications']
            );
            
            // Cek member pakai Eloquent (lebih bersih)
            if (!$conversation->members()->where('user_id', $owner->id)->exists()) {
                $conversation->members()->attach($owner->id);
            }
            
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => null,
                'is_system' => true,
                'body' => $message,
                'meta' => json_encode([
                    'barang_id' => $this->id,
                    'user_id' => $user->id,
                    'old_stok' => $oldStok,
                    'new_stok' => $newStok,
                ]),
            ]);
        }
    }
}