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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            // Ini adalah kolom penghubung ke tabel customers
            $table->foreignId('customer_id')
                ->constrained() // Memastikan 'customer_id' merujuk ke 'id' di tabel 'customers'
                ->cascadeOnDelete(); // Jika pelanggan dihapus, data mobilnya ikut terhapus

            $table->string('license_plate')->unique(); // Nomor Polisi, harus unik
            $table->string('brand'); // Merek, e.g., Toyota
            $table->string('model'); // Model, e.g., Avanza
            $table->string('color')->nullable(); // Warna
            $table->year('year'); // Tahun pembuatan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
