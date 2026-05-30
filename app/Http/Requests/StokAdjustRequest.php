<?php
// app/Http/Requests/StokAdjustRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StokAdjustRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya owner & admin yang boleh adjust
        return in_array($this->user()->role, ['owner', 'admin']);
    }

    public function rules(): array
    {
        return [
            'barang_id' => 'required|exists:barang,id',
            'stok_baru' => 'required|integer|min:0',
            'keterangan' => 'required|string|max:500',
        ];
    }
    
    public function messages(): array
    {
        return [
            'stok_baru.required' => 'Stok baru harus diisi',
            'stok_baru.min' => 'Stok baru minimal 0',
            'keterangan.required' => 'Keterangan adjust wajib diisi',
        ];
    }
}