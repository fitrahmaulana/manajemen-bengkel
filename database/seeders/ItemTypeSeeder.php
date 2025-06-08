<?php

namespace Database\Seeders;

use App\Models\TypeItem;
use Illuminate\Database\Seeder;

class ItemTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Oli & Pelumas',
                'description' => 'Berbagai jenis oli mesin, oli transmisi, dan pelumas lainnya'
            ],
            [
                'name' => 'Suku Cadang Mesin',
                'description' => 'Komponen-komponen mesin seperti piston, ring, bearing, dll'
            ],
            [
                'name' => 'Suku Cadang Elektrik',
                'description' => 'Komponen kelistrikan seperti aki, alternator, starter, dll'
            ],
            [
                'name' => 'Filter',
                'description' => 'Filter oli, filter udara, filter bahan bakar, dll'
            ],
            [
                'name' => 'Rem & Kopling',
                'description' => 'Komponen sistem rem dan kopling seperti kampas rem, master rem, dll'
            ],
            [
                'name' => 'Perlengkapan',
                'description' => 'Perlengkapan tambahan seperti wiper, lampu, kaca spion, dll'
            ],
        ];

        foreach ($types as $type) {
            TypeItem::create($type);
        }
    }
}
