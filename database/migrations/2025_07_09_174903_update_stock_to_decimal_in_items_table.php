<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Mengubah field stock dari integer menjadi decimal(15,2) untuk mendukung nilai seperti 3.5 liter
            $table->decimal('stock', 15, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Kembalikan ke integer jika rollback
            $table->integer('stock')->default(0)->change();
        });
    }
};
