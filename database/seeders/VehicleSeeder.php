<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicles = [
            [
                'customer_id' => 1,
                'license_plate' => 'B 1234 ABC',
                'brand' => 'Toyota',
                'model' => 'Avanza',
                'color' => 'Putih',
                'year' => 2020,
            ],
            [
                'customer_id' => 1,
                'license_plate' => 'B 5678 DEF',
                'brand' => 'Honda',
                'model' => 'Civic',
                'color' => 'Hitam',
                'year' => 2021,
            ],
            [
                'customer_id' => 2,
                'license_plate' => 'B 9012 GHI',
                'brand' => 'Suzuki',
                'model' => 'Ertiga',
                'color' => 'Silver',
                'year' => 2019,
            ],
            [
                'customer_id' => 3,
                'license_plate' => 'B 3456 JKL',
                'brand' => 'Daihatsu',
                'model' => 'Xenia',
                'color' => 'Merah',
                'year' => 2018,
            ],
            [
                'customer_id' => 4,
                'license_plate' => 'B 7890 MNO',
                'brand' => 'Mitsubishi',
                'model' => 'Pajero',
                'color' => 'Biru',
                'year' => 2022,
            ],
            [
                'customer_id' => 5,
                'license_plate' => 'B 1357 PQR',
                'brand' => 'Nissan',
                'model' => 'Serena',
                'color' => 'Abu-abu',
                'year' => 2020,
            ],
            [
                'customer_id' => 6,
                'license_plate' => 'B 2468 STU',
                'brand' => 'Mazda',
                'model' => 'CX-5',
                'color' => 'Putih',
                'year' => 2021,
            ],
            [
                'customer_id' => 7,
                'license_plate' => 'B 9753 VWX',
                'brand' => 'Hyundai',
                'model' => 'Creta',
                'color' => 'Hitam',
                'year' => 2023,
            ],
            [
                'customer_id' => 8,
                'license_plate' => 'B 8642 YZA',
                'brand' => 'Isuzu',
                'model' => 'Panther',
                'color' => 'Coklat',
                'year' => 2017,
            ],
        ];

        foreach ($vehicles as $vehicle) {
            Vehicle::create($vehicle);
        }
    }
}
