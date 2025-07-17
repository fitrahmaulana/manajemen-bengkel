<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->nullableMorphs('payable');
        });

        DB::table('payments')->whereNotNull('invoice_id')->update([
            'payable_type' => Invoice::class,
            'payable_id' => DB::raw('invoice_id'),
        ]);

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
        });

        DB::table('payments')
            ->where('payable_type', Invoice::class)
            ->update([
                'invoice_id' => DB::raw('payable_id'),
            ]);

        Schema::table('payments', function (Blueprint $table) {
            $table->dropMorphs('payable');
        });
    }
};
