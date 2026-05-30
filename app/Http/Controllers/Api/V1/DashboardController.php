<?php
// app/Http/Controllers/Api/V1/DashboardController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\TransaksiStok;
use App\Models\User;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $data = match($user->role) {
            'owner' => $this->ownerDashboard(),
            'admin' => $this->adminDashboard(),
            'gudang' => $this->gudangDashboard(),
            default => []
        };
        
        return response()->json($data);
    }
    
    private function ownerDashboard(): array
    {
        // Total barang
        $totalBarang = Barang::count();
        
        // Total stok keseluruhan
        $totalStok = Barang::sum('stok');
        
        // Barang stok menipis
        $stokMenipis = Barang::whereRaw('stok <= stok_minimal')->get(['id', 'nama', 'stok', 'stok_minimal']);
        
        // Transaksi hari ini
        $transaksiHariIni = TransaksiStok::whereDate('created_at', today())->count();
        
        // User aktif (login hari ini) - dari aktivitas_user
        $userAktif = User::whereHas('aktivitasUser', function($q) {
            $q->whereDate('created_at', today())
            ->where('aksi', 'login');
        })->count();
        
        // Stok masuk vs keluar (7 hari terakhir) - PERBAIKAN DI SINI
        $stokFlow = TransaksiStok::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw("SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE 0 END) as masuk"),
                DB::raw("SUM(CASE WHEN jenis = 'keluar' THEN jumlah ELSE 0 END) as keluar")
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get();
        
        // Unread system messages
        $unreadMessages = Message::where('is_system', true)
            ->whereNull('read_at')
            ->count();
        
        return [
            'role' => 'owner',
            'statistics' => [
                'total_barang' => $totalBarang,
                'total_stok' => $totalStok,
                'transaksi_hari_ini' => $transaksiHariIni,
                'user_aktif' => $userAktif,
                'unread_system_messages' => $unreadMessages,
            ],
            'stok_menipis' => $stokMenipis,
            'stok_flow_7_hari' => $stokFlow,
        ];
    }
    
    private function adminDashboard(): array
    {
        $totalBarang = Barang::count();
        $totalStok = Barang::sum('stok');
        $stokMenipis = Barang::whereRaw('stok <= stok_minimal')->get(['id', 'nama', 'stok', 'stok_minimal']);
        $transaksiHariIni = TransaksiStok::whereDate('created_at', today())->count();
        
        return [
            'role' => 'admin',
            'statistics' => [
                'total_barang' => $totalBarang,
                'total_stok' => $totalStok,
                'transaksi_hari_ini' => $transaksiHariIni,
            ],
            'stok_menipis' => $stokMenipis,
        ];
    }
    
    private function gudangDashboard(): array
    {
        $user = request()->user();
        
        // Barang dengan stok menipis (info saja)
        $stokMenipis = Barang::whereRaw('stok <= stok_minimal')->get(['id', 'nama', 'stok', 'stok_minimal']);
        
        // Transaksi yang dilakukan user ini hari ini
        $transaksiSayaHariIni = TransaksiStok::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->count();
        
        // Unread personal messages
        $unreadMessages = Message::where('is_system', false)
            ->whereNull('read_at')
            ->whereHas('conversation.members', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->count();
        
        return [
            'role' => 'gudang',
            'statistics' => [
                'transaksi_saya_hari_ini' => $transaksiSayaHariIni,
                'unread_messages' => $unreadMessages,
            ],
            'stok_menipis' => $stokMenipis,
        ];
    }
}