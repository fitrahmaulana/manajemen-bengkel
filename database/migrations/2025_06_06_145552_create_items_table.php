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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama Barang, misal: "Filter Oli Avanza"
            $table->string('sku')->unique(); // Kode Barang/SKU, harus unik
            $table->string('brand')->nullable(); // Merek, misal: "Toyota Genuine Part"
            $table->decimal('purchase_price', 15, 2); // Harga Beli (modal)
            $table->decimal('selling_price', 15, 2); // Harga Jual
            $table->integer('stock')->default(0); // Jumlah Stok
            $table->string('unit', 50)->default('Pcs'); // Satuan, misal: Pcs, Botol, Set
            $table->string('location')->nullable(); // Lokasi penyimpanan, misal: "Rak A-01"
            $table->foreignId('type_item_id')->nullable()->constrained()->onDelete('set null');

            // Fields for stock conversion
            $table->string('parent_sku')->nullable()->comment('SKU of the parent item if this is an eceran item');
            $table->decimal('conversion_value', 8, 2)->nullable()->comment('How many base units this item represents if it can be broken down');
            $table->string('base_unit')->nullable()->comment('The base unit for eceran items, e.g., Liter');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
