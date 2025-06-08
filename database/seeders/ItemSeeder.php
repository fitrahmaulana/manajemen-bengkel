<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\TypeItem;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        // Get type IDs
        $oliType = TypeItem::where('name', 'Oli & Pelumas')->first();
        $mesinType = TypeItem::where('name', 'Suku Cadang Mesin')->first();
        $elektrikType = TypeItem::where('name', 'Suku Cadang Elektrik')->first();
        $filterType = TypeItem::where('name', 'Filter')->first();
        $remType = TypeItem::where('name', 'Rem & Kopling')->first();
        $perlengkapanType = TypeItem::where('name', 'Perlengkapan')->first();

        $items = [
            // Oli & Pelumas
            [
                'name' => 'Oli Mesin Shell Helix HX7 10W-40',
                'sku' => 'OLI-SH-001',
                'brand' => 'Shell',
                'purchase_price' => 85000,
                'selling_price' => 95000,
                'stock' => 20,
                'unit' => 'Pcs',
                'location' => 'Rak A-01',
                'type_item_id' => $oliType->id,
            ],
            [
                'name' => 'Oli Mesin Castrol Magnatec 5W-40',
                'sku' => 'OLI-CS-001',
                'brand' => 'Castrol',
                'purchase_price' => 95000,
                'selling_price' => 105000,
                'stock' => 15,
                'unit' => 'Pcs',
                'location' => 'Rak A-02',
                'type_item_id' => $oliType->id,
            ],

            // Suku Cadang Mesin
            [
                'name' => 'Piston Kit Toyota Avanza 1.3',
                'sku' => 'PST-TY-001',
                'brand' => 'Toyota Genuine',
                'purchase_price' => 2500000,
                'selling_price' => 2800000,
                'stock' => 3,
                'unit' => 'Set',
                'location' => 'Rak B-01',
                'type_item_id' => $mesinType->id,
            ],
            [
                'name' => 'Ring Piston Honda Brio',
                'sku' => 'RNG-HN-001',
                'brand' => 'Honda Genuine',
                'purchase_price' => 450000,
                'selling_price' => 500000,
                'stock' => 5,
                'unit' => 'Set',
                'location' => 'Rak B-02',
                'type_item_id' => $mesinType->id,
            ],

            // Suku Cadang Elektrik
            [
                'name' => 'Aki GS Battery 45D23L',
                'sku' => 'AKI-GS-001',
                'brand' => 'GS Battery',
                'purchase_price' => 850000,
                'selling_price' => 950000,
                'stock' => 8,
                'unit' => 'Pcs',
                'location' => 'Rak C-01',
                'type_item_id' => $elektrikType->id,
            ],
            [
                'name' => 'Alternator Toyota Avanza',
                'sku' => 'ALT-TY-001',
                'brand' => 'Toyota Genuine',
                'purchase_price' => 1500000,
                'selling_price' => 1700000,
                'stock' => 2,
                'unit' => 'Pcs',
                'location' => 'Rak C-02',
                'type_item_id' => $elektrikType->id,
            ],

            // Filter
            [
                'name' => 'Filter Oli Toyota Avanza',
                'sku' => 'FLT-TY-001',
                'brand' => 'Toyota Genuine',
                'purchase_price' => 45000,
                'selling_price' => 55000,
                'stock' => 25,
                'unit' => 'Pcs',
                'location' => 'Rak D-01',
                'type_item_id' => $filterType->id,
            ],
            [
                'name' => 'Filter Udara Honda Brio',
                'sku' => 'FLT-HN-001',
                'brand' => 'Honda Genuine',
                'purchase_price' => 35000,
                'selling_price' => 45000,
                'stock' => 20,
                'unit' => 'Pcs',
                'location' => 'Rak D-02',
                'type_item_id' => $filterType->id,
            ],

            // Rem & Kopling
            [
                'name' => 'Kampas Rem Depan Toyota Avanza',
                'sku' => 'KMP-TY-001',
                'brand' => 'Toyota Genuine',
                'purchase_price' => 250000,
                'selling_price' => 300000,
                'stock' => 10,
                'unit' => 'Set',
                'location' => 'Rak E-01',
                'type_item_id' => $remType->id,
            ],
            [
                'name' => 'Kampas Kopling Honda Brio',
                'sku' => 'KMP-HN-001',
                'brand' => 'Honda Genuine',
                'purchase_price' => 350000,
                'selling_price' => 400000,
                'stock' => 8,
                'unit' => 'Set',
                'location' => 'Rak E-02',
                'type_item_id' => $remType->id,
            ],

            // Perlengkapan
            [
                'name' => 'Wiper Blade Toyota Avanza',
                'sku' => 'WPR-TY-001',
                'brand' => 'Toyota Genuine',
                'purchase_price' => 85000,
                'selling_price' => 100000,
                'stock' => 15,
                'unit' => 'Set',
                'location' => 'Rak F-01',
                'type_item_id' => $perlengkapanType->id,
            ],
            [
                'name' => 'Lampu Depan Honda Brio',
                'sku' => 'LMP-HN-001',
                'brand' => 'Honda Genuine',
                'purchase_price' => 450000,
                'selling_price' => 500000,
                'stock' => 5,
                'unit' => 'Pcs',
                'location' => 'Rak F-02',
                'type_item_id' => $perlengkapanType->id,
            ],
        ];

        foreach ($items as $item) {
            Item::create($item);
        }
    }
}
