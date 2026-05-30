<?php
// database/migrations/2025_01_01_000005_create_barang_fotos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barang')->onDelete('cascade');
            $table->string('url');
            $table->string('public_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->integer('urutan')->default(0);
            $table->timestamps();
            
            $table->index('barang_id');
            $table->index('is_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_fotos');
    }
};