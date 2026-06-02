<?php
// app/Http/Controllers/Api/V1/BarangController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BarangRequest;
use App\Models\Barang;
use App\Models\BarangFoto;
use App\Models\AktivitasUser;
use App\Models\HistoriBarang;
use App\Models\TransaksiStok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;  // ← TAMBAHKAN UNTUK LOCAL STORAGE

class BarangController extends Controller
{
    /**
     * Upload foto ke local storage
     */
    /**
     * Upload foto ke local storage
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @param int $barangId
     * @param int $index
     * @return array{url: string, public_id: string}
     */
    private function uploadFoto($file, $barangId, $index)
    {
        $filename = "barang_{$barangId}_{$index}_" . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('barang', $filename, 'public');
        
        // Gunakan APP_URL + Storage::url
        $fullUrl = env('APP_URL') . Storage::url($path);
        
        return [
            'url' => $fullUrl,
            'public_id' => $path,
        ];
    }
    
    /**
     * Hapus foto dari local storage
     * 
     * @param string|null $publicId
     */
    private function deleteStorageFile(?string $publicId): void
    {
        if ($publicId && Storage::disk('public')->exists($publicId)) {
            Storage::disk('public')->delete($publicId);
        }
    }

    public function index(Request $request)
    {
        $query = Barang::with(['kategori', 'supplier', 'fotos']);
        
        // Filter by kategori
        if ($request->has('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }
        
        // Filter by supplier
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        
        // Search by nama or sku
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('nama', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%");
            });
        }
        
        // Filter stok menipis
        if ($request->boolean('stok_menipis')) {
            $query->whereRaw('stok <= stok_minimal');
        }
        
        // Sorting
        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);
        
        $perPage = $request->per_page ?? 15;
        $barang = $query->paginate($perPage);
        
        return response()->json([
            'message' => 'success',
            'data' => $barang
        ]);
    }

    public function store(BarangRequest $request)
    {
        DB::beginTransaction();
        
        try {
            // Create barang
            $barang = Barang::create([
                'nama' => $request->nama,
                'sku' => $request->sku,
                'deskripsi' => $request->deskripsi,
                'stok' => $request->stok_awal ?? 0,
                'stok_minimal' => $request->stok_minimal ?? 0,
                'satuan' => $request->satuan,
                'kategori_id' => $request->kategori_id,
                'supplier_id' => $request->supplier_id,
                'created_by' => $request->user()->id,
            ]);
            
            // Handle foto upload ke local storage
            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $index => $foto) {
                    $uploadResult = $this->uploadFoto($foto, $barang->id, $index);
                    
                    BarangFoto::create([
                        'barang_id' => $barang->id,
                        'url' => $uploadResult['url'],
                        'public_id' => $uploadResult['public_id'],
                        'is_primary' => $index === 0,
                        'urutan' => $index,
                    ]);
                }
            }
            
            // Jika stok awal > 0, catat sebagai transaksi stok masuk
            if (($request->stok_awal ?? 0) > 0) {
                TransaksiStok::create([
                    'barang_id' => $barang->id,
                    'user_id' => $request->user()->id,
                    'jenis' => 'masuk',
                    'jumlah' => $request->stok_awal,
                    'stok_sebelum' => 0,
                    'stok_sesudah' => $request->stok_awal,
                    'keterangan' => 'Stok awal saat pembuatan barang',
                ]);
                
                HistoriBarang::create([
                    'barang_id' => $barang->id,
                    'user_id' => $request->user()->id,
                    'field' => 'stok',
                    'old_value' => 0,
                    'new_value' => $request->stok_awal,
                    'aksi' => 'create',
                    'ip_address' => $request->ip(),
                ]);
            }
            
            // Log aktivitas
            AktivitasUser::log(
                $request->user(),
                'create_barang',
                'Barang',
                $barang->id,
                "Membuat barang: {$barang->nama} (SKU: {$barang->sku})"
            );
            
            DB::commit();
            
            return response()->json([
                'message' => 'Barang berhasil dibuat',
                'data' => $barang->load(['kategori', 'supplier', 'fotos'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Gagal membuat barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Barang $barang)
    {
        $barang->load(['kategori', 'supplier', 'fotos', 'createdBy', 'transaksiStok' => function($q) {
            $q->latest()->limit(20);
        }, 'historiBarang' => function($q) {
            $q->latest()->limit(20);
        }]);
        
        return response()->json([
            'message' => 'success',
            'data' => $barang
        ]);
    }

    public function update(BarangRequest $request, Barang $barang)
    {
        DB::beginTransaction();
        
        try {
            $oldData = $barang->toArray();
            $barang->update($request->except(['stok_awal', 'fotos']));
            
            // Catat perubahan ke histori
            $changes = $barang->getChanges();
            foreach ($changes as $field => $newValue) {
                if ($field !== 'updated_at') {
                    HistoriBarang::create([
                        'barang_id' => $barang->id,
                        'user_id' => $request->user()->id,
                        'field' => $field,
                        'old_value' => $oldData[$field] ?? null,
                        'new_value' => $newValue,
                        'aksi' => 'update',
                        'ip_address' => $request->ip(),
                    ]);
                }
            }
            
            // Handle upload foto baru (jika ada) ke local storage
            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $index => $foto) {
                    $uploadResult = $this->uploadFoto($foto, $barang->id, $barang->fotos()->count() + $index);
                    
                    BarangFoto::create([
                        'barang_id' => $barang->id,
                        'url' => $uploadResult['url'],
                        'public_id' => $uploadResult['public_id'],
                        'is_primary' => $barang->fotos()->count() === 0,
                        'urutan' => $barang->fotos()->count(),
                    ]);
                }
            }
            
            AktivitasUser::log(
                $request->user(),
                'update_barang',
                'Barang',
                $barang->id,
                "Update barang: {$barang->nama}"
            );
            
            DB::commit();
            
            return response()->json([
                'message' => 'Barang berhasil diupdate',
                'data' => $barang->load(['kategori', 'supplier', 'fotos'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Gagal mengupdate barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, Barang $barang)
    {
        DB::beginTransaction();
        
        try {
            // 1. Hapus foto dari local storage
            foreach ($barang->fotos as $foto) {
                $this->deleteStorageFile($foto->public_id);
            }
            
            // 2. Hapus semua transaksi stok terkait
            TransaksiStok::where('barang_id', $barang->id)->delete();
            
            // 3. Hapus semua histori barang terkait
            HistoriBarang::where('barang_id', $barang->id)->delete();
            
            // 4. Hapus foto dari database
            BarangFoto::where('barang_id', $barang->id)->delete();
            
            $namaBarang = $barang->nama;
            
            // 5. Hapus barang
            $barang->delete();
            
            // 6. Log aktivitas
            AktivitasUser::log(
                $request->user(),
                'delete_barang',
                'Barang',
                $barang->id,
                "Menghapus barang: {$namaBarang} beserta semua riwayatnya"
            );
            
            DB::commit();
            
            return response()->json([
                'message' => 'Barang berhasil dihapus'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Gagal menghapus barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // Hapus foto barang tertentu
    public function deleteFoto(Request $request, Barang $barang, BarangFoto $foto)
    {
        if ($foto->barang_id !== $barang->id) {
            return response()->json(['message' => 'Foto tidak ditemukan'], 404);
        }
        
        // Hapus dari local storage
        $this->deleteStorageFile($foto->public_id);  // ← rename
        
        $foto->delete();
        
        // Set foto pertama sebagai primary jika primary yang dihapus
        if ($foto->is_primary) {
            $newPrimary = $barang->fotos()->first();
            if ($newPrimary) {
                $newPrimary->update(['is_primary' => true]);
            }
        }
        
        return response()->json([
            'message' => 'Foto berhasil dihapus'
        ]);
    }
    
    // Set foto sebagai primary
    public function setPrimaryFoto(Request $request, Barang $barang, BarangFoto $foto)
    {
        if ($foto->barang_id !== $barang->id) {
            return response()->json(['message' => 'Foto tidak ditemukan'], 404);
        }
        
        $barang->fotos()->update(['is_primary' => false]);
        $foto->update(['is_primary' => true]);
        
        return response()->json([
            'message' => 'Foto utama berhasil diubah'
        ]);
    }
}