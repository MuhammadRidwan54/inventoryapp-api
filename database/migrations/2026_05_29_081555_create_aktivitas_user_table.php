<?php
// database/migrations/2025_01_01_000011_create_aktivitas_user_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aktivitas_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('aksi'); // login, logout, create_barang, update_stok, dll
            $table->string('model')->nullable(); // Barang, TransaksiStok, dll
            $table->unsignedBigInteger('model_id')->nullable(); // ID dari model yang diubah
            $table->text('detail')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            // Index untuk query cepat
            $table->index('user_id');
            $table->index('aksi');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aktivitas_user');
    }
};