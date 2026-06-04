<?php
// database/migrations/2026_06_04_000001_add_is_active_to_barang_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            // Tambahkan kolom is_active setelah kolom created_by
            // Default true agar semua data lama tetap aktif
            $table->boolean('is_active')->default(true)->after('created_by');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });
    }
};
