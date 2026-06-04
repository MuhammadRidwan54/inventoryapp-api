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
use App\Services\CloudinaryService;  // ← TAMBAHKAN
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;  // ← TAMBAHKAN

class BarangController extends Controller
{
    protected $cloudinary;

    public function __construct(CloudinaryService $cloudinary)
    {
        $this->cloudinary = $cloudinary;
    }

    public function index(Request $request)
    {
        $query = Barang::with(['kategori', 'supplier', 'fotos']);
        
        if ($request->has('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }
        
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('nama', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%");
            });
        }
        
        if ($request->boolean('stok_menipis')) {
            $query->whereRaw('stok <= stok_minimal');
        }
        
        // Filter status aktif: default hanya tampilkan aktif
        // ?is_active=true  → hanya aktif
        // ?is_active=false → hanya nonaktif
        // ?is_active=all   → semua
        $isActiveParam = $request->get('is_active', 'true');
        if ($isActiveParam !== 'all') {
            $isActive = filter_var($isActiveParam, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }
        
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
        Log::info('=== STORE BARANG ===');
        
        DB::beginTransaction();
        
        try {
            // Create barang
            $barang = Barang::create([
                'nama'         => $request->nama,
                'sku'          => $request->sku,
                'deskripsi'    => $request->deskripsi,
                'stok'         => $request->stok_awal ?? 0,
                'stok_minimal' => $request->stok_minimal ?? 0,
                'satuan'       => $request->satuan,
                'kategori_id'  => $request->kategori_id,
                'supplier_id'  => $request->supplier_id,
                'created_by'   => $request->user()->id,
                'is_active'    => $request->is_active ?? true,
            ]);
            
            Log::info('Barang created: ' . $barang->id);
            
            // Handle foto upload ke CLOUDINARY
            if ($request->hasFile('fotos')) {
                Log::info('Processing fotos...');
                
                foreach ($request->file('fotos') as $index => $foto) {
                    Log::info('Uploading foto ' . $index . ', size: ' . $foto->getSize());
                    
                    try {
                        $uploadResult = $this->cloudinary->upload($foto);
                        Log::info('Cloudinary success: ' . $uploadResult->public_id);
                        
                        BarangFoto::create([
                            'barang_id' => $barang->id,
                            'url' => $uploadResult->url,
                            'public_id' => $uploadResult->public_id,
                            'is_primary' => $index === 0,
                            'urutan' => $index,
                        ]);
                    } catch (\Exception $cloudError) {
                        Log::error('Cloudinary error: ' . $cloudError->getMessage());
                        throw $cloudError;
                    }
                }
            }
            
            // Jika stok awal > 0
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
            
            AktivitasUser::log(
                $request->user(),
                'create_barang',
                'Barang',
                $barang->id,
                "Membuat barang: {$barang->nama} (SKU: {$barang->sku})"
            );
            
            DB::commit();
            Log::info('=== STORE SUCCESS ===');
            
            return response()->json([
                'message' => 'Barang berhasil dibuat',
                'data' => $barang->load(['kategori', 'supplier', 'fotos'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('=== STORE ERROR ===');
            Log::error('Error: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            
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
            
            $changes = $barang->getChanges();
            foreach ($changes as $field => $newValue) {
                if ($field !== 'updated_at') {
                    HistoriBarang::create([
                        'barang_id' => $barang->id,
                        'user_id'   => $request->user()->id,
                        'field'     => $field,
                        'old_value' => $oldData[$field] ?? null,
                        'new_value' => $newValue,
                        'aksi'      => 'update',
                        'ip_address'=> $request->ip(),
                    ]);
                }
            }
            
            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $index => $foto) {
                    $uploadResult = $this->cloudinary->upload($foto);
                    
                    BarangFoto::create([
                        'barang_id' => $barang->id,
                        'url' => $uploadResult->url,
                        'public_id' => $uploadResult->public_id,
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
            Log::error('Update error: ' . $e->getMessage());
            
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
            // Hapus foto dari Cloudinary
            foreach ($barang->fotos as $foto) {
                if ($foto->public_id) {
                    $this->cloudinary->delete($foto->public_id);
                }
            }
            
            TransaksiStok::where('barang_id', $barang->id)->delete();
            HistoriBarang::where('barang_id', $barang->id)->delete();
            BarangFoto::where('barang_id', $barang->id)->delete();
            
            $namaBarang = $barang->nama;
            $barang->delete();
            
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
            Log::error('Delete error: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Gagal menghapus barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function deleteFoto(Request $request, Barang $barang, BarangFoto $foto)
    {
        if ($foto->barang_id !== $barang->id) {
            return response()->json(['message' => 'Foto tidak ditemukan'], 404);
        }
        
        if ($foto->public_id) {
            $this->cloudinary->delete($foto->public_id);
        }
        
        $foto->delete();
        
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

    public function uploadFoto(Request $request, Barang $barang)
    {
        $request->validate([
            'fotos' => 'required|array',
            'fotos.*' => 'image|mimes:jpeg,png,jpg|max:5120'
        ]);

        $uploadedFotos = [];
        
        foreach ($request->file('fotos') as $index => $foto) {
            try {
                $uploadResult = $this->cloudinary->upload($foto);
                
                $barangFoto = BarangFoto::create([
                    'barang_id' => $barang->id,
                    'url' => $uploadResult->url,
                    'public_id' => $uploadResult->public_id,
                    'is_primary' => $barang->fotos()->count() === 0,
                    'urutan' => $barang->fotos()->count(),
                ]);
                
                $uploadedFotos[] = $barangFoto;
                
            } catch (\Exception $e) {
                Log::error('Upload foto error: ' . $e->getMessage());
                return response()->json([
                    'message' => 'Gagal upload foto',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        
        return response()->json([
            'message' => 'Foto berhasil diupload',
            'data' => $uploadedFotos
        ]);
    }

    public function toggleActive(Request $request, Barang $barang)
    {
        DB::beginTransaction();
        
        try {
            $oldStatus = $barang->is_active;
            $newStatus = !$oldStatus;
            
            $barang->update(['is_active' => $newStatus]);
            
            HistoriBarang::create([
                'barang_id' => $barang->id,
                'user_id'   => $request->user()->id,
                'field'     => 'is_active',
                'old_value' => $oldStatus ? '1' : '0',
                'new_value' => $newStatus ? '1' : '0',
                'aksi'      => 'update',
                'ip_address'=> $request->ip(),
            ]);
            
            $statusText = $newStatus ? 'mengaktifkan' : 'menonaktifkan';
            AktivitasUser::log(
                $request->user(),
                'toggle_active_barang',
                'Barang',
                $barang->id,
                ucfirst($statusText) . " barang: {$barang->nama}"
            );
            
            DB::commit();
            
            return response()->json([
                'message' => "Barang berhasil di" . ($newStatus ? 'aktifkan' : 'nonaktifkan'),
                'data' => $barang
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Toggle active error: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Gagal mengubah status aktif barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}