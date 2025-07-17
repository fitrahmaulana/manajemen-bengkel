<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Supplier::create([
            'name' => 'PT. Sumber Oli',
            'sales_name' => 'Budi',
            'phone_number' => '081234567890',
            'email' => 'sumber@oli.com',
            'address' => 'Jl. Oli No. 1, Jakarta',
        ]);

        Supplier::create([
            'name' => 'CV. Jaya Filter',
            'sales_name' => 'Joko',
            'phone_number' => '087654321098',
            'email' => 'jaya@filter.com',
            'address' => 'Jl. Filter No. 2, Surabaya',
        ]);
    }
}
