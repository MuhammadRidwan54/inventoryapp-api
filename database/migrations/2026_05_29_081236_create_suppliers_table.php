<?php
// database/migrations/2025_01_01_000003_create_suppliers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kontak')->nullable();
            $table->string('email')->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();
            
            $table->index('nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};