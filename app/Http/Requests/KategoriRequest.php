<?php
// app/Http/Requests/KategoriRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $kategoriId = $this->route('kategori')?->id;
        
        return [
            'nama' => ['required', 'string', 'max:100', Rule::unique('kategori', 'nama')->ignore($kategoriId)],
            'deskripsi' => 'nullable|string',
        ];
    }
}