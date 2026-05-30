<?php
// app/Http/Requests/BarangRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $barangId = $this->route('barang')?->id;
        
        return [
            'nama' => 'required|string|max:255',
            'sku' => ['required', 'string', 'max:100', Rule::unique('barang', 'sku')->ignore($barangId)],
            'deskripsi' => 'nullable|string',
            'stok_awal' => 'required_if:is_create,true|nullable|integer|min:0',
            'stok_minimal' => 'nullable|integer|min:0',
            'satuan' => 'required|string|max:50|in:pcs,kg,gram,meter,liter,unit',
            'kategori_id' => 'required|exists:kategori,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'fotos' => 'nullable|array',
            'fotos.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
    
    protected function prepareForValidation(): void
    {
        // Tandai bahwa ini create, bukan update
        if (!$this->route('barang')) {
            $this->merge(['is_create' => true]);
        }
    }
    
    public function messages(): array
    {
        return [
            'sku.unique' => 'SKU sudah digunakan, gunakan SKU yang berbeda',
            'kategori_id.required' => 'Kategori harus dipilih',
            'fotos.*.image' => 'File harus berupa gambar',
            'fotos.*.max' => 'Ukuran gambar maksimal 2MB',
        ];
    }
}