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
        Schema::table('item_stock_conversions', function (Blueprint $table) {
            // Mengubah from_quantity dan to_quantity menjadi decimal untuk mendukung desimal
            $table->decimal('from_quantity', 15, 2)->comment('Jumlah item asal yang dikonversi')->change();
            $table->decimal('to_quantity', 15, 2)->comment('Jumlah item tujuan yang dihasilkan')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_stock_conversions', function (Blueprint $table) {
            // Kembalikan ke integer jika rollback
            $table->integer('from_quantity')->comment('Jumlah item asal yang dikonversi')->change();
            $table->integer('to_quantity')->comment('Jumlah item tujuan yang dihasilkan')->change();
        });
    }
};
