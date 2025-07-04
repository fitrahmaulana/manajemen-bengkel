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
        // This migration is no longer needed due to schema restructuring
        // The is_convertible functionality is now handled by the target_child_item_id field
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is no longer needed due to schema restructuring
    }
};
