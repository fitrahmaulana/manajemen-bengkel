<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Budi Santoso',
                'phone_number' => '08123456789',
                'address' => 'Jl. Merdeka No. 123, Jakarta Pusat',
            ],
            [
                'name' => 'Siti Nurhaliza',
                'phone_number' => '08234567890',
                'address' => 'Jl. Sudirman No. 456, Jakarta Selatan',
            ],
            [
                'name' => 'Ahmad Wijaya',
                'phone_number' => '08345678901',
                'address' => 'Jl. Gatot Subroto No. 789, Jakarta Barat',
            ],
            [
                'name' => 'Ratna Dewi',
                'phone_number' => '08456789012',
                'address' => 'Jl. Kebon Jeruk No. 321, Jakarta Barat',
            ],
            [
                'name' => 'Dedi Kurniawan',
                'phone_number' => '08567890123',
                'address' => 'Jl. Cempaka Putih No. 654, Jakarta Pusat',
            ],
            [
                'name' => 'Maya Sari',
                'phone_number' => '08678901234',
                'address' => 'Jl. Kemang No. 987, Jakarta Selatan',
            ],
            [
                'name' => 'Roni Hermawan',
                'phone_number' => '08789012345',
                'address' => 'Jl. Thamrin No. 135, Jakarta Pusat',
            ],
            [
                'name' => 'Lina Kartika',
                'phone_number' => '08890123456',
                'address' => 'Jl. Kuningan No. 246, Jakarta Selatan',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}
