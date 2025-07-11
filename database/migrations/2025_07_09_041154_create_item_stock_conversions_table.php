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
        Schema::create('item_stock_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_item_id')->constrained('items')->comment('Item asal yang dikonversi');
            $table->foreignId('to_item_id')->constrained('items')->comment('Item tujuan hasil konversi');
            $table->decimal('from_quantity', 15, 2)->comment('Jumlah item asal yang dikonversi');
            $table->decimal('to_quantity', 15, 2)->comment('Jumlah item tujuan yang dihasilkan');
            $table->foreignId('user_id')->nullable()->constrained('users')->comment('User yang melakukan konversi');
            $table->timestamp('conversion_date')->default(now());
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_stock_conversions');
    }
};
