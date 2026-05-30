<?php
// app/Http/Controllers/Api/V1/SupplierController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use App\Models\AktivitasUser;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query();
        
        if ($request->has('search')) {
            $query->where('nama', 'like', "%{$request->search}%")
                  ->orWhere('kontak', 'like', "%{$request->search}%");
        }
        
        $perPage = $request->per_page ?? 15;
        $suppliers = $query->latest()->paginate($perPage);
        
        return response()->json([
            'message' => 'success',
            'data' => $suppliers
        ]);
    }
    
    public function store(SupplierRequest $request)
    {
        $supplier = Supplier::create($request->validated());
        
        AktivitasUser::log(
            $request->user(),
            'create_supplier',
            'Supplier',
            $supplier->id,
            "Membuat supplier: {$supplier->nama}"
        );
        
        return response()->json([
            'message' => 'Supplier berhasil dibuat',
            'data' => $supplier
        ], 201);
    }
    
    public function show(Supplier $supplier)
    {
        return response()->json([
            'message' => 'success',
            'data' => $supplier->load('barang')
        ]);
    }
    
    public function update(SupplierRequest $request, Supplier $supplier)
    {
        $oldNama = $supplier->nama;
        $supplier->update($request->validated());
        
        AktivitasUser::log(
            $request->user(),
            'update_supplier',
            'Supplier',
            $supplier->id,
            "Update supplier: {$oldNama} -> {$supplier->nama}"
        );
        
        return response()->json([
            'message' => 'Supplier berhasil diupdate',
            'data' => $supplier
        ]);
    }
    
    public function destroy(Request $request, Supplier $supplier)
    {
        if ($supplier->barang()->count() > 0) {
            return response()->json([
                'message' => 'Supplier tidak bisa dihapus karena masih memiliki barang'
            ], 422);
        }
        
        $namaSupplier = $supplier->nama;
        $supplier->delete();
        
        AktivitasUser::log(
            $request->user(),
            'delete_supplier',
            'Supplier',
            $supplier->id,
            "Menghapus supplier: {$namaSupplier}"
        );
        
        return response()->json([
            'message' => 'Supplier berhasil dihapus'
        ]);
    }
    
    public function all(Request $request)
    {
        $suppliers = Supplier::select('id', 'nama')->orderBy('nama')->get();
        
        return response()->json([
            'message' => 'success',
            'data' => $suppliers
        ]);
    }
}