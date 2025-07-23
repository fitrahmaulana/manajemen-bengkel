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
                'name' => 'Spare Parts',
                'description' => 'Suku cadang kendaraan seperti kampas rem, filter, busi, dll',
            ],
            [
                'name' => 'Oli & Cairan',
                'description' => 'Berbagai jenis oli mesin, oli transmisi, cairan rem, coolant, dll',
            ],
            [
                'name' => 'Ban & Velg',
                'description' => 'Ban mobil, velg racing, ban dalam, dll',
            ],
            [
                'name' => 'Aksesoris',
                'description' => 'Aksesoris kendaraan seperti karpet, cover, parfum mobil, dll',
            ],
        ];

        foreach ($types as $type) {
            TypeItem::create($type);
        }
    }
}
