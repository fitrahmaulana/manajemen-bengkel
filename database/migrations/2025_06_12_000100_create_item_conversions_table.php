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
        Schema::create('item_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_item_id')->constrained('items')->onDelete('cascade');
            $table->foreignId('child_item_id')->constrained('items')->onDelete('cascade');
            $table->decimal('conversion_value', 8, 2); // e.g., 1 parent unit = X child units
            $table->timestamps();

            $table->unique(['parent_item_id', 'child_item_id'], 'parent_child_conversion_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_conversions');
    }
};
