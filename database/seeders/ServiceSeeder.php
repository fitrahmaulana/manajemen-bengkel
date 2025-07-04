<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'Ganti Oli Mesin',
                'description' => 'Penggantian oli mesin dengan oli berkualitas tinggi',
                'price' => 150000,
            ],
            [
                'name' => 'Tune Up',
                'description' => 'Service rutin untuk menjaga performa mesin',
                'price' => 300000,
            ],
            [
                'name' => 'Ganti Ban',
                'description' => 'Penggantian ban mobil dengan ban baru',
                'price' => 500000,
            ],
            [
                'name' => 'Servis AC',
                'description' => 'Pembersihan dan perbaikan sistem AC mobil',
                'price' => 250000,
            ],
            [
                'name' => 'Ganti Kampas Rem',
                'description' => 'Penggantian kampas rem untuk keamanan berkendara',
                'price' => 200000,
            ],
            [
                'name' => 'Balancing Roda',
                'description' => 'Penyeimbangan roda untuk mengurangi getaran',
                'price' => 100000,
            ],
            [
                'name' => 'Spooring',
                'description' => 'Penyesuaian geometri roda untuk handling yang optimal',
                'price' => 150000,
            ],
            [
                'name' => 'Ganti Filter Udara',
                'description' => 'Penggantian filter udara untuk sirkulasi udara yang bersih',
                'price' => 75000,
            ],
            [
                'name' => 'Cuci Mobil',
                'description' => 'Pencucian mobil bagian dalam dan luar',
                'price' => 50000,
            ],
            [
                'name' => 'Ganti Aki',
                'description' => 'Penggantian aki mobil dengan aki baru',
                'price' => 800000,
            ],
            [
                'name' => 'Servis Injeksi',
                'description' => 'Pembersihan sistem injeksi bahan bakar',
                'price' => 400000,
            ],
            [
                'name' => 'Ganti Timing Belt',
                'description' => 'Penggantian timing belt untuk mencegah kerusakan mesin',
                'price' => 600000,
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
