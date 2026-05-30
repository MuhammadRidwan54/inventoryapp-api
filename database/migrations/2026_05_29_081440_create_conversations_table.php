<?php
// database/migrations/2025_01_01_000008_create_conversations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('jenis'); // personal, system
            $table->string('judul')->nullable();
            $table->timestamps();
            
            $table->index('jenis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};