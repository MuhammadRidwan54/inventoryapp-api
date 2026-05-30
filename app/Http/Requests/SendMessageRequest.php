<?php
// app/Http/Requests/SendMessageRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'conversation_id' => 'nullable|exists:conversations,id',
            'user_id' => 'required_without:conversation_id|exists:users,id',
            'body' => 'required|string|min:1|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Pesan tidak boleh kosong',
            'user_id.required_without' => 'User ID atau Conversation ID harus diisi',
            'user_id.exists' => 'User tidak ditemukan',
        ];
    }
}