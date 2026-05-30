<?php
// app/Http/Requests/SupplierRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplierId = $this->route('supplier')?->id;
        
        return [
            'nama' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'nama')->ignore($supplierId)],
            'kontak' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'alamat' => 'nullable|string',
        ];
    }
}