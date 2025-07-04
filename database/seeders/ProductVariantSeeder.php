<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Item;
use App\Models\TypeItem;

class ProductVariantSeeder extends Seeder
{
    public function run()
    {
        // Pastikan ada type item
        $typeItem = TypeItem::first() ?? TypeItem::create([
            'name' => 'Spare Part',
            'description' => 'Suku cadang kendaraan'
        ]);

        // Produk dengan varian
        $productWithVariants = Product::create([
            'name' => 'Oli Mesin',
            'brand' => 'Pertamina',
            'type_item_id' => $typeItem->id,
            'has_variants' => true,
        ]);

        // Varian oli mesin
        $variants = [
            [
                'name' => 'Oli Mesin 5W-30 - 1 Liter',
                'sku' => 'OLI-5W30-1L',
                'unit' => 'Liter',
                'purchase_price' => 45000,
                'selling_price' => 55000,
                'stock' => 15,
            ],
            [
                'name' => 'Oli Mesin 5W-30 - 4 Liter',
                'sku' => 'OLI-5W30-4L',
                'unit' => 'Galon',
                'purchase_price' => 170000,
                'selling_price' => 200000,
                'stock' => 8,
            ],
            [
                'name' => 'Oli Mesin 10W-40 - 1 Liter',
                'sku' => 'OLI-10W40-1L',
                'unit' => 'Liter',
                'purchase_price' => 40000,
                'selling_price' => 50000,
                'stock' => 12,
            ],
            [
                'name' => 'Oli Mesin 10W-40 - 4 Liter',
                'sku' => 'OLI-10W40-4L',
                'unit' => 'Galon',
                'purchase_price' => 150000,
                'selling_price' => 180000,
                'stock' => 0, // Habis stok
            ],
        ];

        foreach ($variants as $variant) {
            Item::create([
                'product_id' => $productWithVariants->id,
                'name' => $variant['name'],
                'sku' => $variant['sku'],
                'unit' => $variant['unit'],
                'purchase_price' => $variant['purchase_price'],
                'selling_price' => $variant['selling_price'],
                'stock' => $variant['stock'],
            ]);
        }

        // Produk tanpa varian (standard)
        $productStandard = Product::create([
            'name' => 'Filter Udara',
            'brand' => 'Denso',
            'type_item_id' => $typeItem->id,
            'has_variants' => false,
        ]);

        // Item untuk produk standard
        Item::create([
            'product_id' => $productStandard->id,
            'name' => 'Filter Udara',
            'sku' => 'FLT-UDARA-001',
            'unit' => 'Pcs',
            'purchase_price' => 35000,
            'selling_price' => 45000,
            'stock' => 25,
        ]);

        // Produk dengan varian lain
        $productBanVariants = Product::create([
            'name' => 'Ban Motor',
            'brand' => 'Michelin',
            'type_item_id' => $typeItem->id,
            'has_variants' => true,
        ]);

        // Varian ban motor
        $banVariants = [
            [
                'name' => 'Ban Motor 80/90-17',
                'sku' => 'BAN-80-90-17',
                'unit' => 'Pcs',
                'purchase_price' => 150000,
                'selling_price' => 180000,
                'stock' => 10,
            ],
            [
                'name' => 'Ban Motor 90/90-17',
                'sku' => 'BAN-90-90-17',
                'unit' => 'Pcs',
                'purchase_price' => 165000,
                'selling_price' => 200000,
                'stock' => 8,
            ],
            [
                'name' => 'Ban Motor 100/90-17',
                'sku' => 'BAN-100-90-17',
                'unit' => 'Pcs',
                'purchase_price' => 180000,
                'selling_price' => 220000,
                'stock' => 6,
            ],
        ];

        foreach ($banVariants as $variant) {
            Item::create([
                'product_id' => $productBanVariants->id,
                'name' => $variant['name'],
                'sku' => $variant['sku'],
                'unit' => $variant['unit'],
                'purchase_price' => $variant['purchase_price'],
                'selling_price' => $variant['selling_price'],
                'stock' => $variant['stock'],
            ]);
        }

        $this->command->info('Product variants seeded successfully!');
    }
}
