<?php
// database/migrations/2025_01_01_000007_create_histori_barang_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('histori_barang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barang')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('field');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('aksi');
            $table->string('ip_address')->nullable();
            $table->timestamps();
            
            $table->index('barang_id');
            $table->index('user_id');
            $table->index('created_at');
            $table->index(['barang_id', 'field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('histori_barang');
    }
};