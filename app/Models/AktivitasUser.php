<?php
// app/Models/AktivitasUser.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class AktivitasUser extends Model
{
    use HasFactory;

    protected $table = 'aktivitas_user';
    
    protected $fillable = [
        'user_id',
        'aksi',
        'model',
        'model_id',
        'detail',
        'ip_address',
        'user_agent',
    ];

    public function user(): Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper untuk mencatat aktivitas
    /**
     * @param User $user
     * @param string $aksi
     * @param string|null $model
     * @param int|null $modelId
     * @param string|null $detail
     * @return self
     */
    public static function log(User $user, string $aksi, ?string $model = null, ?int $modelId = null, ?string $detail = null): self
    {
        return self::create([
            'user_id' => $user->id,
            'aksi' => $aksi,
            'model' => $model,
            'model_id' => $modelId,
            'detail' => $detail,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}