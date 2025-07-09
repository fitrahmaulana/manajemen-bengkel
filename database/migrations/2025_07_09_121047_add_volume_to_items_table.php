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
            $table->decimal('volume_value', 10, 2)->nullable()->after('unit')->comment('Nilai volume item, misal 1000 untuk 1000ml');
            $table->string('base_volume_unit', 50)->nullable()->after('volume_value')->comment('Satuan dasar volume, misal ml, gr, pcs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['volume_value', 'base_volume_unit']);
        });
    }
};
