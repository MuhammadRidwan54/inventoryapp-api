<?php
// app/Http/Requests/StokMasukRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StokMasukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'barang_id' => 'required|exists:barang,id',
            'jumlah' => 'required|integer|min:1',
            'keterangan' => 'nullable|string|max:500',
            'referensi' => 'nullable|string|max:100', // No invoice, PO, etc
        ];
    }
    
    public function messages(): array
    {
        return [
            'barang_id.required' => 'Barang harus dipilih',
            'barang_id.exists' => 'Barang tidak ditemukan',
            'jumlah.required' => 'Jumlah stok harus diisi',
            'jumlah.min' => 'Jumlah stok minimal 1',
        ];
    }
}