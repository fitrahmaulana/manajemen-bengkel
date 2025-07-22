<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin123'),
        ]);

        $this->call([
            ItemTypeSeeder::class,
            SupplierSeeder::class,
            ProductSeeder::class,
            ItemSeeder::class,
            ServiceSeeder::class,
            CustomerSeeder::class,
            VehicleSeeder::class,
            InvoiceSeeder::class,
            InvoiceItemServiceSeederUpdated::class, // Menggunakan seeder yang sudah diperbaiki
            PaymentSeeder::class,
        ]);
    }
}
