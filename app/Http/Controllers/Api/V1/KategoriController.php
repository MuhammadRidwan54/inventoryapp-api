<?php
// app/Http/Controllers/Api/V1/KategoriController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\KategoriRequest;
use App\Models\Kategori;
use App\Models\AktivitasUser;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    public function index(Request $request)
    {
        $query = Kategori::query();
        
        // Search
        if ($request->has('search')) {
            $query->where('nama', 'like', "%{$request->search}%");
        }
        
        // Pagination
        $perPage = $request->per_page ?? 15;
        $kategori = $query->latest()->paginate($perPage);
        
        return response()->json([
            'message' => 'success',
            'data' => $kategori
        ]);
    }
    
    public function store(KategoriRequest $request)
    {
        $kategori = Kategori::create($request->validated());
        
        AktivitasUser::log(
            $request->user(),
            'create_kategori',
            'Kategori',
            $kategori->id,
            "Membuat kategori: {$kategori->nama}"
        );
        
        return response()->json([
            'message' => 'Kategori berhasil dibuat',
            'data' => $kategori
        ], 201);
    }
    
    public function show(Kategori $kategori)
    {
        return response()->json([
            'message' => 'success',
            'data' => $kategori->load('barang')
        ]);
    }
    
    public function update(KategoriRequest $request, Kategori $kategori)
    {
        $oldNama = $kategori->nama;
        $kategori->update($request->validated());
        
        AktivitasUser::log(
            $request->user(),
            'update_kategori',
            'Kategori',
            $kategori->id,
            "Update kategori: {$oldNama} -> {$kategori->nama}"
        );
        
        return response()->json([
            'message' => 'Kategori berhasil diupdate',
            'data' => $kategori
        ]);
    }
    
    public function destroy(Request $request, Kategori $kategori)
    {
        // Cek apakah kategori masih punya barang
        if ($kategori->barang()->count() > 0) {
            return response()->json([
                'message' => 'Kategori tidak bisa dihapus karena masih memiliki barang'
            ], 422);
        }
        
        $namaKategori = $kategori->nama;
        $kategori->delete();
        
        AktivitasUser::log(
            $request->user(),
            'delete_kategori',
            'Kategori',
            $kategori->id,
            "Menghapus kategori: {$namaKategori}"
        );
        
        return response()->json([
            'message' => 'Kategori berhasil dihapus'
        ]);
    }
    
    public function all(Request $request)
    {
        // Untuk dropdown/select
        $kategori = Kategori::select('id', 'nama')->orderBy('nama')->get();
        
        return response()->json([
            'message' => 'success',
            'data' => $kategori
        ]);
    }
}