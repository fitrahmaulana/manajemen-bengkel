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
        // Create products table first
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Oli HX7" (nama umum produk)
            $table->string('brand')->nullable(); // "Shell"
            $table->text('description')->nullable();
            $table->foreignId('type_item_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('has_variants')->default(false); // Apakah produk ini punya varian (items)
            $table->timestamps();
        });

        // Create items table
        Schema::create('items', function (Blueprint $table) {
            $table->id();

            // Relasi ke product (untuk grouping varian)
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // Info varian
            $table->string('name')->nullable(); // "1 Liter", "4 Liter", "Eceran"
            $table->string('sku')->unique(); // "HX7-1L", "HX7-4L", "HX7-ECER"

            // Harga & stok
            $table->decimal('purchase_price', 15, 0);
            $table->decimal('selling_price', 15, 0);
            $table->decimal('stock', 15, 2)->default(0); // Changed to decimal for fractional quantities like 3.5 liter
            $table->string('unit', 50)->default('Pcs'); // "Liter", "Botol", dll

            // Tambahan volume
            $table->decimal('volume_value', 10, 2)->nullable()->comment('Nilai volume item, misal 1000 untuk 1000ml');
            $table->string('base_volume_unit', 50)->nullable()->comment('Satuan dasar volume, misal ml, gr, pcs');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop items table first (due to foreign key constraint)
        Schema::dropIfExists('items');

        // Then drop products table
        Schema::dropIfExists('products');
    }
};
