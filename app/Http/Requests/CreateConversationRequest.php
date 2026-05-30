<?php
// app/Http/Requests/CreateConversationRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'judul' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID harus diisi',
            'user_id.exists' => 'User tidak ditemukan',
        ];
    }
}