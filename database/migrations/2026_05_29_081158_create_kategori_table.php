<?php
// database/migrations/2025_01_01_000002_create_kategori_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->text('deskripsi')->nullable();
            $table->timestamps();
            
            $table->index('nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori');
    }
};