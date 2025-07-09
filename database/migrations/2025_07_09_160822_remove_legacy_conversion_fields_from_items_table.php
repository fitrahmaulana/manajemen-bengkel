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
            // Check if the columns exist before trying to drop them to avoid errors on re-runs or if already removed.
            if (Schema::hasColumn('items', 'conversion_value')) {
                $table->dropColumn('conversion_value');
            }
            // Drop foreign key for target_child_item_id before dropping the column
            if (Schema::hasColumn('items', 'target_child_item_id')) {
                // Attempt to drop foreign key. Laravel uses convention table_column_foreign.
                // If this fails due to custom name, it might need manual adjustment or specific name.
                try {
                    $table->dropForeign(['target_child_item_id']);
                } catch (\Exception $e) {
                    // Log or handle if needed, or assume it might not exist if schema was cleaned up before
                    // For this script, we'll proceed to try dropping column anyway.
                    // In a real scenario, you might want to be more careful here.
                    // info("Could not drop foreign key for target_child_item_id: " . $e->getMessage());
                }
                $table->dropColumn('target_child_item_id');
            }
            if (Schema::hasColumn('items', 'is_convertible')) {
                $table->dropColumn('is_convertible');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Re-add the columns if rolling back
            $table->boolean('is_convertible')->default(false)->after('base_volume_unit'); // Adjust position as needed
            $table->foreignId('target_child_item_id')->nullable()->constrained('items')->onDelete('set null')->after('is_convertible');
            $table->decimal('conversion_value', 8, 2)->nullable()->after('target_child_item_id');
        });
    }
};
