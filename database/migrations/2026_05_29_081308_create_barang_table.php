<?php
// database/migrations/2025_01_01_000004_create_barang_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('sku')->unique();
            $table->text('deskripsi')->nullable();
            $table->integer('stok')->default(0);
            $table->integer('stok_minimal')->default(0);
            $table->string('satuan')->default('pcs');
            $table->foreignId('kategori_id')->constrained('kategori')->onDelete('restrict');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            
            $table->index('nama');
            $table->index('sku');
            $table->index('stok');
            $table->index('kategori_id');
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};