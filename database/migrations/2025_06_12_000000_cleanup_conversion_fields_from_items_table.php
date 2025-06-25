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
            // Drop foreign key constraints before dropping columns, if they exist
            // Check if the column exists before trying to drop it to avoid errors
            // The foreign key name might vary depending on how it was created.
            // A common convention is items_parent_item_id_foreign.
            // If unsure, this part might need manual adjustment or inspection of schema.

            if (Schema::hasColumn('items', 'parent_item_id')) {
                // Attempt to find and drop foreign key. This is a best guess for the name.
                // If this fails, it means the FK name is different or doesn't exist.
                try {
                    $foreignKeys = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('items');
                    foreach ($foreignKeys as $foreignKey) {
                        if (in_array('parent_item_id', $foreignKey->getLocalColumns())) {
                            $table->dropForeign($foreignKey->getName());
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    // Log or handle error if necessary, e.g., foreign key not found
                }
                $table->dropColumn('parent_item_id');
            }

            if (Schema::hasColumn('items', 'conversion_value')) {
                $table->dropColumn('conversion_value');
            }

            if (Schema::hasColumn('items', 'base_unit')) {
                $table->dropColumn('base_unit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Re-add columns if rolling back.
            // Note: Data will be lost. This is a structural rollback.
            if (!Schema::hasColumn('items', 'parent_item_id')) {
                $table->foreignId('parent_item_id')->nullable()->constrained('items')->onDelete('set null')->comment('Old field, for rollback');
            }
            if (!Schema::hasColumn('items', 'conversion_value')) {
                $table->decimal('conversion_value', 8, 2)->nullable()->comment('Old field, for rollback');
            }
            if (!Schema::hasColumn('items', 'base_unit')) {
                $table->string('base_unit')->nullable()->comment('Old field, for rollback');
            }
        });
    }
};
