<?php
// database/migrations/2025_01_01_000006_create_transaksi_stok_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barang')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('jenis'); // masuk, keluar, adjust
            $table->integer('jumlah');
            $table->integer('stok_sebelum');
            $table->integer('stok_sesudah');
            $table->text('keterangan')->nullable();
            $table->string('referensi')->nullable();
            $table->timestamps();
            
            $table->index('barang_id');
            $table->index('user_id');
            $table->index('jenis');
            $table->index('created_at');
            $table->index(['barang_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_stok');
    }
};