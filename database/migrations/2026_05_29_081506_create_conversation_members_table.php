<?php
// database/migrations/2025_01_01_000009_create_conversation_members_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            
            // Unique: satu user tidak bisa double join di satu conversation
            $table->unique(['conversation_id', 'user_id']);
            $table->index('user_id');
            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_members');
    }
};