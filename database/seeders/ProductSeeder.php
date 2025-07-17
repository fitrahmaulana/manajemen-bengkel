<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\TypeItem;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // Pastikan ada type item
        $oliType = TypeItem::where('name', 'Oli & Cairan')->first() ?? TypeItem::first();
        $sparePartType = TypeItem::where('name', 'Spare Parts')->first() ?? TypeItem::first();

        // 1. Oli Shell HX7 10W-40 dengan varian kemasan
        Product::create([
            'name' => 'Oli Shell HX7 10W-40',
            'brand' => 'Shell',
            'description' => 'Oli mesin semi sintetik dengan teknologi terdepan',
            'type_item_id' => $oliType->id,
            'has_variants' => true,
        ]);

        // 2. Oli Castrol GTX 20W-50 dengan varian kemasan
        Product::create([
            'name' => 'Oli Castrol GTX 20W-50',
            'brand' => 'Castrol',
            'description' => 'Oli mesin konvensional untuk performa optimal',
            'type_item_id' => $oliType->id,
            'has_variants' => true,
        ]);

        // 3. Busi NGK G-Power dengan varian tipe
        Product::create([
            'name' => 'Busi NGK G-Power',
            'brand' => 'NGK',
            'description' => 'Busi dengan teknologi platinum untuk performa maksimal',
            'type_item_id' => $sparePartType->id,
            'has_variants' => true,
        ]);

        // 4. Filter Udara Denso dengan varian mobil
        Product::create([
            'name' => 'Filter Udara Denso',
            'brand' => 'Denso',
            'description' => 'Filter udara original dengan kualitas OEM',
            'type_item_id' => $sparePartType->id,
            'has_variants' => true,
        ]);

        // 5. Kampas Rem Bendix dengan varian posisi
        Product::create([
            'name' => 'Kampas Rem Bendix Toyota Avanza',
            'brand' => 'Bendix',
            'description' => 'Kampas rem berkualitas tinggi dengan formula ceramic',
            'type_item_id' => $sparePartType->id,
            'has_variants' => true,
        ]);

        // 6. Aki GS Astra - Produk TANPA varian (sesuai invoice)
        Product::create([
            'name' => 'Aki GS Astra NS40ZL',
            'brand' => 'GS Astra',
            'description' => 'Aki mobil dengan teknologi maintenance free',
            'type_item_id' => $sparePartType->id,
            'has_variants' => false,
        ]);

        // 7. Ban Bridgestone - Produk TANPA varian
        Product::create([
            'name' => 'Ban Bridgestone Turanza T005 185/65R15',
            'brand' => 'Bridgestone',
            'description' => 'Ban premium dengan teknologi terdepan untuk kenyamanan berkendara',
            'type_item_id' => $sparePartType->id,
            'has_variants' => false,
        ]);

        // 8. Timing Belt Gates dengan varian mobil
        Product::create([
            'name' => 'Timing Belt Gates',
            'brand' => 'Gates',
            'description' => 'Timing belt berkualitas tinggi untuk berbagai jenis mobil',
            'type_item_id' => $sparePartType->id,
            'has_variants' => true,
        ]);

        // 9. Coolant Prestone - Produk TANPA varian
        Product::create([
            'name' => 'Coolant Prestone Universal',
            'brand' => 'Prestone',
            'description' => 'Coolant universal untuk semua jenis mesin',
            'type_item_id' => $oliType->id,
            'has_variants' => false,
        ]);

        // 10. Minyak Rem Bosch dengan varian tipe
        Product::create([
            'name' => 'Minyak Rem Bosch',
            'brand' => 'Bosch',
            'description' => 'Minyak rem berkualitas tinggi untuk sistem rem yang optimal',
            'type_item_id' => $oliType->id,
            'has_variants' => true,
        ]);

        $this->command->info('Real product variants seeded successfully!');
    }
}
