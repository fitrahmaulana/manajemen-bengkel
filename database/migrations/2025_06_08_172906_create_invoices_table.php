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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();

            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date');

            $table->string('status')->default('unpaid'); // Pilihan: draft, sent, paid, overdue

            $table->decimal('subtotal', 15, 0)->default(0);
            $table->string('discount_type')->nullable(); // Pilihan: 'percentage' atau 'fixed'
            $table->decimal('discount_value', 15, 0)->default(0);
            $table->decimal('total_amount', 15, 0)->default(0);

            $table->text('terms')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
