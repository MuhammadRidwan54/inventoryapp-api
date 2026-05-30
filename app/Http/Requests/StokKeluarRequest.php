<?php
// app/Http/Requests/StokKeluarRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StokKeluarRequest extends FormRequest
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
            'referensi' => 'nullable|string|max:100',
        ];
    }
    
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $barang = \App\Models\Barang::find($this->barang_id);
            if ($barang && $this->jumlah > $barang->stok) {
                $validator->errors()->add('jumlah', "Stok tidak mencukupi. Stok saat ini: {$barang->stok}");
            }
        });
    }
}