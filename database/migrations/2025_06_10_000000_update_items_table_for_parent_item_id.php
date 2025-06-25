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
            // Remove parent_sku if it exists
            if (Schema::hasColumn('items', 'parent_sku')) {
                $table->dropColumn('parent_sku');
            }

            // Add parent_item_id
            $table->foreignId('parent_item_id')->nullable()->constrained('items')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['parent_item_id']);
            $table->dropColumn('parent_item_id');

            // Add parent_sku back if needed for rollback (optional, depends on strategy)
            // For simplicity, we'll assume it's okay not to perfectly restore parent_sku
            // if it was used with actual data. A more robust rollback might require
            // data transformation if data was migrated from parent_sku to parent_item_id.
            // $table->string('parent_sku')->nullable()->comment('SKU of the parent item if this is an eceran item');
        });
    }
};
