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

            // New fields for single target child conversion
            $table->foreignId('target_child_item_id')->nullable()->constrained('items')->onDelete('set null')->comment('ID of the eceran item this parent converts to');
            $table->decimal('conversion_value', 8, 2)->nullable()->comment('How many units of target_child_item_id are made from 1 unit of this item');
            $table->string('base_unit')->nullable()->comment('The base unit of the conversion (e.g., Liter, Pcs)');
            // is_convertible is handled by a separate migration (2025_06_13_000000_add_is_convertible_to_items_table.php)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // If rolling back, ensure to drop the foreign key if it was added, then the column.
        // This down() method should ideally reverse what up() does.
        // Given we are modifying an existing create_table, a full rollback would drop the table.
        // If this migration was ever run with the old fields, then a more complex down()
        // would be needed to restore them, or a separate rollback migration.
        // For simplicity, assuming a fresh migrate:fresh scenario if this is modified.
        Schema::dropIfExists('items');
    }
};
