<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sumberOliId = Supplier::where('name', 'PT. Sumber Oli')->first()->id;
        $jayaFilterId = Supplier::where('name', 'CV. Jaya Filter')->first()->id;

        $oliShellHX7 = Product::where('name', 'Oli Shell HX7 10W-40')->first();
        $oliCastrolGTX = Product::where('name', 'Oli Castrol GTX 20W-50')->first();
        $busiNGK = Product::where('name', 'Busi NGK G-Power')->first();
        $filterDenso = Product::where('name', 'Filter Udara Denso')->first();
        $kampasRemBendix = Product::where('name', 'Kampas Rem Bendix Toyota Avanza')->first();
        $akiGS = Product::where('name', 'Aki GS Astra NS40ZL')->first();
        $banBridgestone = Product::where('name', 'Ban Bridgestone Turanza T005 185/65R15')->first();
        $timingBeltGates = Product::where('name', 'Timing Belt Gates')->first();
        $coolantPrestone = Product::where('name', 'Coolant Prestone Universal')->first();
        $minyakRemBosch = Product::where('name', 'Minyak Rem Bosch')->first();

        $oliHX7Variants = [
            [
                'name' => '1 Liter',
                'sku' => 'SHL-HX7-10W40-1L',
                'unit' => 'Botol',
                'purchase_price' => 75000,
                'selling_price' => 85000,
                'stock' => 25,
                'minimum_stock' => 5,
                'volume_value' => 1,
                'base_volume_unit' => 'liter',
                'supplier_id' => $sumberOliId,
            ],
            [
                'name' => '4 Liter',
                'sku' => 'SHL-HX7-10W40-4L',
                'unit' => 'Galon',
                'purchase_price' => 285000,
                'selling_price' => 320000,
                'stock' => 12,
                'minimum_stock' => 3,
                'volume_value' => 4,
                'base_volume_unit' => 'liter',
                'supplier_id' => $sumberOliId,
            ],
        ];

        foreach ($oliHX7Variants as $variant) {
            Item::create(array_merge($variant, ['product_id' => $oliShellHX7->id]));
        }

        $oliGTXVariants = [
            [
                'name' => '1 Liter',
                'sku' => 'CST-GTX-20W50-1L',
                'unit' => 'Botol',
                'purchase_price' => 65000,
                'selling_price' => 75000,
                'stock' => 20,
                'minimum_stock' => 5,
                'volume_value' => 1,
                'base_volume_unit' => 'liter',
                'supplier_id' => $sumberOliId,
            ],
            [
                'name' => '4 Liter',
                'sku' => 'CST-GTX-20W50-4L',
                'unit' => 'Galon',
                'purchase_price' => 245000,
                'selling_price' => 280000,
                'stock' => 8,
                'minimum_stock' => 2,
                'volume_value' => 4,
                'base_volume_unit' => 'liter',
                'supplier_id' => $sumberOliId,
            ],
        ];

        foreach ($oliGTXVariants as $variant) {
            Item::create(array_merge($variant, ['product_id' => $oliCastrolGTX->id]));
        }

        $busiNGKVariants = [
            [
                'name' => 'BPR6ES',
                'sku' => 'NGK-GP-BPR6ES',
                'unit' => 'Pcs',
                'purchase_price' => 45000,
                'selling_price' => 55000,
                'stock' => 30,
                'minimum_stock' => 10,
                'supplier_id' => $jayaFilterId,
            ],
            [
                'name' => 'BPR7ES',
                'sku' => 'NGK-GP-BPR7ES',
                'unit' => 'Pcs',
                'purchase_price' => 45000,
                'selling_price' => 55000,
                'stock' => 25,
                'minimum_stock' => 10,
                'supplier_id' => $jayaFilterId,
            ],
            [
                'name' => 'BPR8ES',
                'sku' => 'NGK-GP-BPR8ES',
                'unit' => 'Pcs',
                'purchase_price' => 45000,
                'selling_price' => 55000,
                'stock' => 20,
                'minimum_stock' => 10,
                'supplier_id' => $jayaFilterId,
            ],
        ];

        foreach ($busiNGKVariants as $variant) {
            Item::create(array_merge($variant, ['product_id' => $busiNGK->id]));
        }

        $filterDensoVariants = [
            [
                'name' => 'Toyota Avanza',
                'sku' => 'DNS-FA-AVANZA',
                'unit' => 'Pcs',
                'purchase_price' => 85000,
                'selling_price' => 100000,
                'stock' => 15,
                'minimum_stock' => 5,
                'supplier_id' => $jayaFilterId,
            ],
            [
                'name' => 'Honda Civic',
                'sku' => 'DNS-FA-CIVIC',
                'unit' => 'Pcs',
                'purchase_price' => 95000,
                'selling_price' => 110000,
                'stock' => 12,
                'minimum_stock' => 3,
                'supplier_id' => $jayaFilterId,
            ],
            [
                'name' => 'Suzuki Ertiga',
                'sku' => 'DNS-FA-ERTIGA',
                'unit' => 'Pcs',
                'purchase_price' => 90000,
                'selling_price' => 105000,
                'stock' => 10,
                'minimum_stock' => 3,
                'supplier_id' => $jayaFilterId,
            ],
        ];

        foreach ($filterDensoVariants as $variant) {
            Item::create(array_merge($variant, ['product_id' => $filterDenso->id]));
        }

        $kampasRemVariants = [
            [
                'name' => 'Depan',
                'sku' => 'BDX-BR-AVANZA-F',
                'unit' => 'Set',
                'purchase_price' => 185000,
                'selling_price' => 220000,
                'stock' => 18,
                'minimum_stock' => 5,
                'supplier_id' => $jayaFilterId,
            ],
            [
                'name' => 'Belakang',
                'sku' => 'BDX-BR-AVANZA-R',
                'unit' => 'Set',
                'purchase_price' => 155000,
                'selling_price' => 180000,
                'stock' => 15,
                'minimum_stock' => 5,
                'supplier_id' => $jayaFilterId,
            ],
        ];

        foreach ($kampasRemVariants as $variant) {
            Item::create(array_merge($variant, ['product_id' => $kampasRemBendix->id]));
        }

        Item::create([
            'product_id' => $akiGS->id,
            'name' => null,
            'sku' => 'GSA-AKI-NS40ZL',
            'unit' => 'Pcs',
            'purchase_price' => 650000,
            'selling_price' => 750000,
            'stock' => 15,
            'minimum_stock' => 3,
            'supplier_id' => $sumberOliId,
        ]);

        Item::create([
            'product_id' => $banBridgestone->id,
            'name' => null,
            'sku' => 'BRS-T005-185/65R15',
            'unit' => 'Pcs',
            'purchase_price' => 850000,
            'selling_price' => 950000,
            'stock' => 20,
            'minimum_stock' => 5,
            'supplier_id' => $sumberOliId,
        ]);

        $timingBeltVariants = [
            [
                'name' => 'Toyota Avanza',
                'sku' => 'GTS-TB-AVANZA',
                'unit' => 'Set',
                'purchase_price' => 385000,
                'selling_price' => 450000,
                'stock' => 12,
                'minimum_stock' => 3,
                'supplier_id' => $jayaFilterId,
            ],
            [
                'name' => 'Honda Civic',
                'sku' => 'GTS-TB-CIVIC',
                'unit' => 'Set',
                'purchase_price' => 425000,
                'selling_price' => 500000,
                'stock' => 8,
                'minimum_stock' => 2,
                'supplier_id' => $jayaFilterId,
            ],
            [
                'name' => 'Suzuki Ertiga',
                'sku' => 'GTS-TB-ERTIGA',
                'unit' => 'Set',
                'purchase_price' => 395000,
                'selling_price' => 460000,
                'stock' => 10,
                'minimum_stock' => 3,
                'supplier_id' => $jayaFilterId,
            ],
        ];

        foreach ($timingBeltVariants as $variant) {
            Item::create(array_merge($variant, ['product_id' => $timingBeltGates->id]));
        }

        Item::create([
            'product_id' => $coolantPrestone->id,
            'name' => null,
            'sku' => 'PRS-COOL-1L',
            'unit' => 'Botol',
            'purchase_price' => 75000,
            'selling_price' => 90000,
            'stock' => 25,
            'minimum_stock' => 5,
            'volume_value' => 1,
            'base_volume_unit' => 'liter',
            'supplier_id' => $sumberOliId,
        ]);

        $minyakRemVariants = [
            [
                'name' => 'DOT 3',
                'sku' => 'BSH-BR-DOT3',
                'unit' => 'Botol',
                'purchase_price' => 45000,
                'selling_price' => 55000,
                'stock' => 20,
                'minimum_stock' => 5,
                'volume_value' => 1,
                'base_volume_unit' => 'liter',
                'supplier_id' => $sumberOliId,
            ],
            [
                'name' => 'DOT 4',
                'sku' => 'BSH-BR-DOT4',
                'unit' => 'Botol',
                'purchase_price' => 55000,
                'selling_price' => 65000,
                'stock' => 18,
                'minimum_stock' => 5,
                'volume_value' => 1,
                'base_volume_unit' => 'liter',
                'supplier_id' => $sumberOliId,
            ],
        ];

        foreach ($minyakRemVariants as $variant) {
            Item::create(array_merge($variant, ['product_id' => $minyakRemBosch->id]));
        }
    }
}
