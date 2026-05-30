<?php
// app/Http/Controllers/Api/V1/StokController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StokMasukRequest;
use App\Http\Requests\StokKeluarRequest;
use App\Http\Requests\StokAdjustRequest;
use App\Models\Barang;
use App\Models\TransaksiStok;
use App\Models\HistoriBarang;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\AktivitasUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// ========== TAMBAHKAN IMPORT EVENT ==========
use App\Events\StokUpdatedEvent;
use App\Events\StokMenipisEvent;
use App\Events\UnreadCountUpdatedEvent;

class StokController extends Controller
{
    // STOK MASUK
    public function masuk(StokMasukRequest $request)
    {
        $barang = Barang::findOrFail($request->barang_id);
        $stokLama = $barang->stok;
        $stokBaru = $stokLama + $request->jumlah;
        
        DB::beginTransaction();
        
        try {
            // 1. Insert transaksi_stok
            $transaksi = TransaksiStok::create([
                'barang_id' => $barang->id,
                'user_id' => $request->user()->id,
                'jenis' => 'masuk',
                'jumlah' => $request->jumlah,
                'stok_sebelum' => $stokLama,
                'stok_sesudah' => $stokBaru,
                'keterangan' => $request->keterangan,
                'referensi' => $request->referensi,
            ]);
            
            // 2. Update stok barang
            $barang->update(['stok' => $stokBaru]);
            
            // 3. Insert histori_barang
            HistoriBarang::create([
                'barang_id' => $barang->id,
                'user_id' => $request->user()->id,
                'field' => 'stok',
                'old_value' => $stokLama,
                'new_value' => $stokBaru,
                'aksi' => 'stok_masuk',
                'ip_address' => $request->ip(),
            ]);
            
            // 4. System message untuk owner & admin
            $this->sendSystemMessage($request->user(), $barang, 'masuk', $request->jumlah, $stokLama, $stokBaru, $request->keterangan);
            
            // 5. Log aktivitas
            AktivitasUser::log(
                $request->user(),
                'stok_masuk',
                'Barang',
                $barang->id,
                "Stok masuk {$barang->nama}: +{$request->jumlah} (sebelum: {$stokLama}, sesudah: {$stokBaru})"
            );
            
            DB::commit();
            
            // ========== BROADCAST EVENTS ==========
            // Broadcast event stok updated
            broadcast(new StokUpdatedEvent($barang, $request->user()->id, 'masuk', $request->jumlah));
            
            // Cek dan broadcast stok menipis
            if ($barang->isStokMenipis()) {
                broadcast(new StokMenipisEvent($barang));
            }
            
            // Update unread count untuk owner & admin
            $this->broadcastUnreadCountToOwnerAdmin();
            
            return response()->json([
                'message' => 'Stok berhasil ditambahkan',
                'data' => [
                    'barang' => $barang->fresh(),
                    'transaksi' => $transaksi,
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Gagal menambahkan stok',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // STOK KELUAR
    public function keluar(StokKeluarRequest $request)
    {
        $barang = Barang::findOrFail($request->barang_id);
        $stokLama = $barang->stok;
        $stokBaru = $stokLama - $request->jumlah;
        
        DB::beginTransaction();
        
        try {
            // 1. Insert transaksi_stok
            $transaksi = TransaksiStok::create([
                'barang_id' => $barang->id,
                'user_id' => $request->user()->id,
                'jenis' => 'keluar',
                'jumlah' => $request->jumlah,
                'stok_sebelum' => $stokLama,
                'stok_sesudah' => $stokBaru,
                'keterangan' => $request->keterangan,
                'referensi' => $request->referensi,
            ]);
            
            // 2. Update stok barang
            $barang->update(['stok' => $stokBaru]);
            
            // 3. Insert histori_barang
            HistoriBarang::create([
                'barang_id' => $barang->id,
                'user_id' => $request->user()->id,
                'field' => 'stok',
                'old_value' => $stokLama,
                'new_value' => $stokBaru,
                'aksi' => 'stok_keluar',
                'ip_address' => $request->ip(),
            ]);
            
            // 4. System message untuk owner & admin
            $this->sendSystemMessage($request->user(), $barang, 'keluar', $request->jumlah, $stokLama, $stokBaru, $request->keterangan);
            
            // 5. Log aktivitas
            AktivitasUser::log(
                $request->user(),
                'stok_keluar',
                'Barang',
                $barang->id,
                "Stok keluar {$barang->nama}: -{$request->jumlah} (sebelum: {$stokLama}, sesudah: {$stokBaru})"
            );
            
            DB::commit();
            
            // ========== BROADCAST EVENTS ==========
            // Broadcast event stok updated
            broadcast(new StokUpdatedEvent($barang, $request->user()->id, 'keluar', $request->jumlah));
            
            // Cek dan broadcast stok menipis
            if ($barang->isStokMenipis()) {
                broadcast(new StokMenipisEvent($barang));
            }
            
            // Update unread count untuk owner & admin
            $this->broadcastUnreadCountToOwnerAdmin();
            
            return response()->json([
                'message' => 'Stok berhasil dikurangi',
                'data' => [
                    'barang' => $barang->fresh(),
                    'transaksi' => $transaksi,
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Gagal mengurangi stok',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // STOK ADJUST (hanya owner & admin)
    public function adjust(StokAdjustRequest $request)
    {
        $barang = Barang::findOrFail($request->barang_id);
        $stokLama = $barang->stok;
        $stokBaru = $request->stok_baru;
        $selisih = $stokBaru - $stokLama;
        $jenis = $selisih > 0 ? 'masuk' : ($selisih < 0 ? 'keluar' : 'adjust');
        
        DB::beginTransaction();
        
        try {
            // 1. Insert transaksi_stok
            $transaksi = TransaksiStok::create([
                'barang_id' => $barang->id,
                'user_id' => $request->user()->id,
                'jenis' => 'adjust',
                'jumlah' => abs($selisih),
                'stok_sebelum' => $stokLama,
                'stok_sesudah' => $stokBaru,
                'keterangan' => $request->keterangan,
                'referensi' => $request->referensi ?? null,
            ]);
            
            // 2. Update stok barang
            $barang->update(['stok' => $stokBaru]);
            
            // 3. Insert histori_barang
            HistoriBarang::create([
                'barang_id' => $barang->id,
                'user_id' => $request->user()->id,
                'field' => 'stok',
                'old_value' => $stokLama,
                'new_value' => $stokBaru,
                'aksi' => 'stok_adjust',
                'ip_address' => $request->ip(),
            ]);
            
            // 4. System message untuk owner & admin
            $this->sendSystemMessage($request->user(), $barang, 'adjust', abs($selisih), $stokLama, $stokBaru, $request->keterangan);
            
            // 5. Log aktivitas
            AktivitasUser::log(
                $request->user(),
                'stok_adjust',
                'Barang',
                $barang->id,
                "Stok adjust {$barang->nama}: {$stokLama} -> {$stokBaru} (selisih: " . ($selisih > 0 ? "+{$selisih}" : $selisih) . "), alasan: {$request->keterangan}"
            );
            
            DB::commit();
            
            // ========== BROADCAST EVENTS ==========
            // Broadcast event stok updated
            broadcast(new StokUpdatedEvent($barang, $request->user()->id, 'adjust', abs($selisih)));
            
            // Cek dan broadcast stok menipis
            if ($barang->isStokMenipis()) {
                broadcast(new StokMenipisEvent($barang));
            }
            
            // Update unread count untuk owner & admin
            $this->broadcastUnreadCountToOwnerAdmin();
            
            return response()->json([
                'message' => 'Stok berhasil disesuaikan',
                'data' => [
                    'barang' => $barang->fresh(),
                    'transaksi' => $transaksi,
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Gagal menyesuaikan stok',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // ========== HELPER METHOD UNTUK BROADCAST ==========
    
    /**
     * Broadcast unread count ke semua owner dan admin
     */
    private function broadcastUnreadCountToOwnerAdmin()
    {
        $ownerAdmin = User::whereIn('role', ['owner', 'admin'])->get();
        
        foreach ($ownerAdmin as $user) {
            $unreadCount = $this->getUserUnreadCount($user->id);
            broadcast(new UnreadCountUpdatedEvent($user->id, $unreadCount));
        }
    }
    
    /**
     * Hitung jumlah pesan belum dibaca untuk user tertentu
     */
    private function getUserUnreadCount($userId)
    {
        $conversationIds = Conversation::whereHas('members', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->pluck('id');
        
        return Message::whereIn('conversation_id', $conversationIds)
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();
    }
    
    // SYSTEM MESSAGE HELPER
    private function sendSystemMessage($user, $barang, $jenis, $jumlah, $stokLama, $stokBaru, $keterangan = null)
    {
        $jenisText = [
            'masuk' => 'menambah stok',
            'keluar' => 'mengurangi stok',
            'adjust' => 'menyesuaikan stok'
        ][$jenis] ?? $jenis;
        
        $selisihText = $jenis === 'masuk' ? "+{$jumlah}" : ($jenis === 'keluar' ? "-{$jumlah}" : ($stokBaru > $stokLama ? "+" . ($stokBaru - $stokLama) : ($stokBaru < $stokLama ? "-" . ($stokLama - $stokBaru) : "0")));
        
        $messageBody = "[{$user->role}] {$user->name} {$jenisText} {$barang->nama}: {$selisihText} {$barang->satuan}. " .
                       "Stok: {$stokLama} → {$stokBaru} {$barang->satuan}";
        
        if ($keterangan) {
            $messageBody .= " - Keterangan: {$keterangan}";
        }
        
        // Cek stok menipis
        if ($barang->isStokMenipis()) {
            $messageBody .= " ⚠️ PERINGATAN: Stok menipis! (minimal: {$barang->stok_minimal} {$barang->satuan})";
        }
        
        // Dapatkan semua user dengan role owner & admin
        $recipients = User::whereIn('role', ['owner', 'admin'])->get();
        
        // Cari atau buat conversation system
        $conversation = Conversation::firstOrCreate(
            ['jenis' => 'system'],
            ['judul' => 'System Notifications - Stok']
        );
        
        foreach ($recipients as $recipient) {
            // Add member jika belum ada
            if (!$conversation->members()->where('user_id', $recipient->id)->exists()) {
                $conversation->members()->attach($recipient->id);
            }
        }
        
        // Buat message
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => null,
            'is_system' => true,
            'body' => $messageBody,
            'meta' => json_encode([
                'barang_id' => $barang->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'jenis' => $jenis,
                'jumlah' => $jumlah,
                'stok_sebelum' => $stokLama,
                'stok_sesudah' => $stokBaru,
                'keterangan' => $keterangan,
            ]),
        ]);
    }
    
    // ALERT STOK MENIPIS
    private function sendStokMenipisAlert($barang)
    {
        $recipients = User::whereIn('role', ['owner', 'admin'])->get();
        
        $conversation = Conversation::firstOrCreate(
            ['jenis' => 'system'],
            ['judul' => 'System Notifications - Alert']
        );
        
        $messageBody = "⚠️ ALERT STOK MENIPIS! ⚠️\n" .
                       "Barang: {$barang->nama}\n" .
                       "SKU: {$barang->sku}\n" .
                       "Stok saat ini: {$barang->stok} {$barang->satuan}\n" .
                       "Stok minimal: {$barang->stok_minimal} {$barang->satuan}\n" .
                       "Segera lakukan penambahan stok!";
        
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => null,
            'is_system' => true,
            'body' => $messageBody,
            'meta' => json_encode([
                'type' => 'stok_menipis',
                'barang_id' => $barang->id,
                'stok_saat_ini' => $barang->stok,
                'stok_minimal' => $barang->stok_minimal,
            ]),
        ]);
    }
    
    // GET HISTORI STOK BARANG
    public function histori(Request $request, $barangId)
    {
        $barang = Barang::findOrFail($barangId);
        
        $query = TransaksiStok::with('user')
            ->where('barang_id', $barangId);
        
        // Filter by jenis
        if ($request->has('jenis')) {
            $query->where('jenis', $request->jenis);
        }
        
        // Filter date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        $perPage = $request->per_page ?? 15;
        $histori = $query->latest()->paginate($perPage);
        
        return response()->json([
            'message' => 'success',
            'barang' => [
                'id' => $barang->id,
                'nama' => $barang->nama,
                'sku' => $barang->sku,
                'stok_saat_ini' => $barang->stok,
            ],
            'data' => $histori
        ]);
    }
    
    // REKAP STOK (owner & admin only)
    public function rekap(Request $request)
    {
        $query = Barang::with(['kategori', 'supplier']);
        
        // Filter kategori
        if ($request->has('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }
        
        // Filter stok menipis
        if ($request->boolean('stok_menipis')) {
            $query->whereRaw('stok <= stok_minimal');
        }
        
        $barang = $query->get();
        
        $totalStok = $barang->sum('stok');
        $totalBarang = $barang->count();
        $barangMenipis = $barang->filter(fn($b) => $b->isStokMenipis())->count();
        
        return response()->json([
            'message' => 'success',
            'summary' => [
                'total_barang' => $totalBarang,
                'total_stok' => $totalStok,
                'barang_stok_menipis' => $barangMenipis,
            ],
            'data' => $barang
        ]);
    }
    
    // MUTASI STOK per periode (grafik)
    public function mutasi(Request $request)
    {
        $days = $request->days ?? 7;
        
        $mutasi = TransaksiStok::select(
                DB::raw('DATE(created_at) as tanggal'),
                DB::raw('SUM(CASE WHEN jenis = "masuk" THEN jumlah ELSE 0 END) as stok_masuk'),
                DB::raw('SUM(CASE WHEN jenis = "keluar" THEN jumlah ELSE 0 END) as stok_keluar')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('tanggal')
            ->get();
        
        return response()->json([
            'message' => 'success',
            'periode' => "{$days} hari terakhir",
            'data' => $mutasi
        ]);
    }
}