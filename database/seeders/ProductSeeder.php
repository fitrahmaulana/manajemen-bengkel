<?php

namespace Database\Seeders;

use App\Models\Item;
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
        $oliShellHX7 = Product::create([
            'name' => 'Oli Shell HX7 10W-40',
            'brand' => 'Shell',
            'description' => 'Oli mesin semi sintetik dengan teknologi terdepan',
            'type_item_id' => $oliType->id,
            'has_variants' => true,
        ]);

        $oliHX7Variants = [
            [
                'name' => 'Oli Shell HX7 10W-40 - 1 Liter',
                'sku' => 'SHL-HX7-10W40-1L',
                'unit' => 'Botol',
                'purchase_price' => 75000,
                'selling_price' => 85000,
                'stock' => 25,
            ],
            [
                'name' => 'Oli Shell HX7 10W-40 - 4 Liter',
                'sku' => 'SHL-HX7-10W40-4L',
                'unit' => 'Galon',
                'purchase_price' => 285000,
                'selling_price' => 320000,
                'stock' => 12,
            ],
        ];

        foreach ($oliHX7Variants as $variant) {
            Item::create([
                'product_id' => $oliShellHX7->id,
                'name' => $variant['name'],
                'sku' => $variant['sku'],
                'unit' => $variant['unit'],
                'purchase_price' => $variant['purchase_price'],
                'selling_price' => $variant['selling_price'],
                'stock' => $variant['stock'],
            ]);
        }

        // 2. Oli Castrol GTX 20W-50 dengan varian kemasan
        $oliCastrolGTX = Product::create([
            'name' => 'Oli Castrol GTX 20W-50',
            'brand' => 'Castrol',
            'description' => 'Oli mesin konvensional untuk performa optimal',
            'type_item_id' => $oliType->id,
            'has_variants' => true,
        ]);

        $oliGTXVariants = [
            [
                'name' => 'Oli Castrol GTX 20W-50 - 1 Liter',
                'sku' => 'CST-GTX-20W50-1L',
                'unit' => 'Botol',
                'purchase_price' => 65000,
                'selling_price' => 75000,
                'stock' => 20,
            ],
            [
                'name' => 'Oli Castrol GTX 20W-50 - 4 Liter',
                'sku' => 'CST-GTX-20W50-4L',
                'unit' => 'Galon',
                'purchase_price' => 245000,
                'selling_price' => 280000,
                'stock' => 8,
            ],
        ];

        foreach ($oliGTXVariants as $variant) {
            Item::create([
                'product_id' => $oliCastrolGTX->id,
                'name' => $variant['name'],
                'sku' => $variant['sku'],
                'unit' => $variant['unit'],
                'purchase_price' => $variant['purchase_price'],
                'selling_price' => $variant['selling_price'],
                'stock' => $variant['stock'],
            ]);
        }

        // 3. Busi NGK G-Power dengan varian tipe
        $busiNGK = Product::create([
            'name' => 'Busi NGK G-Power',
            'brand' => 'NGK',
            'description' => 'Busi dengan teknologi platinum untuk performa maksimal',
            'type_item_id' => $sparePartType->id,
            'has_variants' => true,
        ]);

        $busiNGKVariants = [
            [
                'name' => 'Busi NGK G-Power BPR6ES',
                'sku' => 'NGK-GP-BPR6ES',
                'unit' => 'Pcs',
                'purchase_price' => 45000,
                'selling_price' => 55000,
                'stock' => 30,
            ],
            [
                'name' => 'Busi NGK G-Power BPR7ES',
                'sku' => 'NGK-GP-BPR7ES',
                'unit' => 'Pcs',
                'purchase_price' => 45000,
                'selling_price' => 55000,
                'stock' => 25,
            ],
            [
                'name' => 'Busi NGK G-Power BPR8ES',
                'sku' => 'NGK-GP-BPR8ES',
                'unit' => 'Pcs',
                'purchase_price' => 45000,
                'selling_price' => 55000,
                'stock' => 20,
            ],
        ];

        foreach ($busiNGKVariants as $variant) {
            Item::create([
                'product_id' => $busiNGK->id,
                'name' => $variant['name'],
                'sku' => $variant['sku'],
                'unit' => $variant['unit'],
                'purchase_price' => $variant['purchase_price'],
                'selling_price' => $variant['selling_price'],
                'stock' => $variant['stock'],
            ]);
        }

        // 4. Filter Udara Denso dengan varian mobil
        $filterDenso = Product::create([
            'name' => 'Filter Udara Denso',
            'brand' => 'Denso',
            'description' => 'Filter udara original dengan kualitas OEM',
            'type_item_id' => $sparePartType->id,
            'has_variants' => true,
        ]);

        $filterDensoVariants = [
            [
                'name' => 'Filter Udara Denso - Toyota Avanza',
                'sku' => 'DNS-FA-AVANZA',
                'unit' => 'Pcs',
                'purchase_price' => 85000,
                'selling_price' => 100000,
                'stock' => 15,
            ],
            [
                'name' => 'Filter Udara Denso - Honda Civic',
                'sku' => 'DNS-FA-CIVIC',
                'unit' => 'Pcs',
                'purchase_price' => 95000,
                'selling_price' => 110000,
                'stock' => 12,
            ],
            [
                'name' => 'Filter Udara Denso - Suzuki Ertiga',
                'sku' => 'DNS-FA-ERTIGA',
                'unit' => 'Pcs',
                'purchase_price' => 90000,
                'selling_price' => 105000,
                'stock' => 10,
            ],
        ];

        foreach ($filterDensoVariants as $variant) {
            Item::create([
                'product_id' => $filterDenso->id,
                'name' => $variant['name'],
                'sku' => $variant['sku'],
                'unit' => $variant['unit'],
                'purchase_price' => $variant['purchase_price'],
                'selling_price' => $variant['selling_price'],
                'stock' => $variant['stock'],
            ]);
        }

        // 5. Kampas Rem Bendix dengan varian posisi
        $kampasRemBendix = Product::create([
            'name' => 'Kampas Rem Bendix Toyota Avanza',
            'brand' => 'Bendix',
            'description' => 'Kampas rem berkualitas tinggi dengan formula ceramic',
            'type_item_id' => $sparePartType->id,
            'has_variants' => true,
        ]);

        $kampasRemVariants = [
            [
                'name' => 'Kampas Rem Bendix Toyota Avanza - Depan',
                'sku' => 'BDX-BR-AVANZA-F',
                'unit' => 'Set',
                'purchase_price' => 185000,
                'selling_price' => 220000,
                'stock' => 18,
            ],
            [
                'name' => 'Kampas Rem Bendix Toyota Avanza - Belakang',
                'sku' => 'BDX-BR-AVANZA-R',
                'unit' => 'Set',
                'purchase_price' => 155000,
                'selling_price' => 180000,
                'stock' => 15,
            ],
        ];

        foreach ($kampasRemVariants as $variant) {
            Item::create([
                'product_id' => $kampasRemBendix->id,
                'name' => $variant['name'],
                'sku' => $variant['sku'],
                'unit' => $variant['unit'],
                'purchase_price' => $variant['purchase_price'],
                'selling_price' => $variant['selling_price'],
                'stock' => $variant['stock'],
            ]);
        }

        // 6. Aki GS Astra - Produk TANPA varian (sesuai invoice)
        $akiGS = Product::create([
            'name' => 'Aki GS Astra NS40ZL',
            'brand' => 'GS Astra',
            'description' => 'Aki mobil dengan teknologi maintenance free',
            'type_item_id' => $sparePartType->id,
            'has_variants' => false,
        ]);

        Item::create([
            'product_id' => $akiGS->id,
            'name' => 'Aki GS Astra NS40ZL',
            'sku' => 'GSA-AKI-NS40ZL',
            'unit' => 'Pcs',
            'purchase_price' => 650000,
            'selling_price' => 750000,
            'stock' => 15,
        ]);

        // 7. Ban Bridgestone - Produk TANPA varian
        $banBridgestone = Product::create([
            'name' => 'Ban Bridgestone Turanza T005',
            'brand' => 'Bridgestone',
            'description' => 'Ban premium dengan teknologi terdepan untuk kenyamanan berkendara',
            'type_item_id' => $sparePartType->id,
            'has_variants' => false,
        ]);

        Item::create([
            'product_id' => $banBridgestone->id,
            'name' => 'Ban Bridgestone Turanza T005 185/65R15',
            'sku' => 'BRS-T005-185/65R15',
            'unit' => 'Pcs',
            'purchase_price' => 850000,
            'selling_price' => 950000,
            'stock' => 20,
        ]);

        // 8. Timing Belt Gates dengan varian mobil
        $timingBeltGates = Product::create([
            'name' => 'Timing Belt Gates',
            'brand' => 'Gates',
            'description' => 'Timing belt berkualitas tinggi untuk berbagai jenis mobil',
            'type_item_id' => $sparePartType->id,
            'has_variants' => true,
        ]);

        $timingBeltVariants = [
            [
                'name' => 'Timing Belt Gates - Toyota Avanza',
                'sku' => 'GTS-TB-AVANZA',
                'unit' => 'Set',
                'purchase_price' => 385000,
                'selling_price' => 450000,
                'stock' => 12,
            ],
            [
                'name' => 'Timing Belt Gates - Honda Civic',
                'sku' => 'GTS-TB-CIVIC',
                'unit' => 'Set',
                'purchase_price' => 425000,
                'selling_price' => 500000,
                'stock' => 8,
            ],
            [
                'name' => 'Timing Belt Gates - Suzuki Ertiga',
                'sku' => 'GTS-TB-ERTIGA',
                'unit' => 'Set',
                'purchase_price' => 395000,
                'selling_price' => 460000,
                'stock' => 10,
            ],
        ];

        foreach ($timingBeltVariants as $variant) {
            Item::create([
                'product_id' => $timingBeltGates->id,
                'name' => $variant['name'],
                'sku' => $variant['sku'],
                'unit' => $variant['unit'],
                'purchase_price' => $variant['purchase_price'],
                'selling_price' => $variant['selling_price'],
                'stock' => $variant['stock'],
            ]);
        }

        // 9. Coolant Prestone - Produk TANPA varian
        $coolantPrestone = Product::create([
            'name' => 'Coolant Prestone Universal',
            'brand' => 'Prestone',
            'description' => 'Coolant universal untuk semua jenis mesin',
            'type_item_id' => $oliType->id,
            'has_variants' => false,
        ]);

        Item::create([
            'product_id' => $coolantPrestone->id,
            'name' => 'Coolant Prestone Universal 1L',
            'sku' => 'PRS-COOL-1L',
            'unit' => 'Botol',
            'purchase_price' => 75000,
            'selling_price' => 90000,
            'stock' => 25,
        ]);

        // 10. Minyak Rem Bosch dengan varian tipe
        $minyakRemBosch = Product::create([
            'name' => 'Minyak Rem Bosch',
            'brand' => 'Bosch',
            'description' => 'Minyak rem berkualitas tinggi untuk sistem rem yang optimal',
            'type_item_id' => $oliType->id,
            'has_variants' => true,
        ]);

        $minyakRemVariants = [
            [
                'name' => 'Minyak Rem Bosch DOT 3',
                'sku' => 'BSH-BR-DOT3',
                'unit' => 'Botol',
                'purchase_price' => 45000,
                'selling_price' => 55000,
                'stock' => 20,
            ],
            [
                'name' => 'Minyak Rem Bosch DOT 4',
                'sku' => 'BSH-BR-DOT4',
                'unit' => 'Botol',
                'purchase_price' => 55000,
                'selling_price' => 65000,
                'stock' => 18,
            ],
        ];

        foreach ($minyakRemVariants as $variant) {
            Item::create([
                'product_id' => $minyakRemBosch->id,
                'name' => $variant['name'],
                'sku' => $variant['sku'],
                'unit' => $variant['unit'],
                'purchase_price' => $variant['purchase_price'],
                'selling_price' => $variant['selling_price'],
                'stock' => $variant['stock'],
            ]);
        }

        $this->command->info('Real product variants seeded successfully!');
    }
}
