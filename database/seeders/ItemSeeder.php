<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Product;
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

        // Create Products and their Items
        $productsData = [
            // Oli & Pelumas
            [
                'product' => [
                    'name' => 'Oli Mesin Shell Helix HX7 10W-40',
                    'brand' => 'Shell',
                    'description' => 'Oli mesin sintetik untuk performa optimal',
                    'type_item_id' => $oliType->id,
                ],
                'items' => [
                    [
                        'name' => '1 Liter',
                        'sku' => 'OLI-SH-001-1L',
                        'purchase_price' => 85000,
                        'selling_price' => 95000,
                        'stock' => 20,
                        'unit' => 'Botol',
                    ]
                ]
            ],
            [
                'product' => [
                    'name' => 'Oli Mesin Castrol Magnatec 5W-40',
                    'brand' => 'Castrol',
                    'description' => 'Oli mesin sintetik dengan teknologi Smart Molecules',
                    'type_item_id' => $oliType->id,
                ],
                'items' => [
                    [
                        'name' => '1 Liter',
                        'sku' => 'OLI-CS-001-1L',
                        'purchase_price' => 95000,
                        'selling_price' => 105000,
                        'stock' => 15,
                        'unit' => 'Botol',
                    ]
                ]
            ],

            // Suku Cadang Mesin
            [
                'product' => [
                    'name' => 'Piston Kit Toyota Avanza 1.3',
                    'brand' => 'Toyota Genuine',
                    'description' => 'Piston kit original untuk Toyota Avanza 1.3L',
                    'type_item_id' => $mesinType->id,
                ],
                'items' => [
                    [
                        'name' => 'Set Lengkap',
                        'sku' => 'PST-TY-001',
                        'purchase_price' => 2500000,
                        'selling_price' => 2800000,
                        'stock' => 3,
                        'unit' => 'Set',
                    ]
                ]
            ],
            [
                'product' => [
                    'name' => 'Ring Piston Honda Brio',
                    'brand' => 'Honda Genuine',
                    'description' => 'Ring piston original untuk Honda Brio',
                    'type_item_id' => $mesinType->id,
                ],
                'items' => [
                    [
                        'name' => 'Set Lengkap',
                        'sku' => 'RNG-HN-001',
                        'purchase_price' => 450000,
                        'selling_price' => 500000,
                        'stock' => 5,
                        'unit' => 'Set',
                    ]
                ]
            ],

            // Suku Cadang Elektrik
            [
                'product' => [
                    'name' => 'Aki GS Battery 45D23L',
                    'brand' => 'GS Battery',
                    'description' => 'Aki kering maintenance free 45Ah',
                    'type_item_id' => $elektrikType->id,
                ],
                'items' => [
                    [
                        'name' => '45Ah',
                        'sku' => 'AKI-GS-001',
                        'purchase_price' => 850000,
                        'selling_price' => 950000,
                        'stock' => 8,
                        'unit' => 'Pcs',
                    ]
                ]
            ],
            [
                'product' => [
                    'name' => 'Alternator Toyota Avanza',
                    'brand' => 'Toyota Genuine',
                    'description' => 'Alternator original untuk Toyota Avanza',
                    'type_item_id' => $elektrikType->id,
                ],
                'items' => [
                    [
                        'name' => 'Unit',
                        'sku' => 'ALT-TY-001',
                        'purchase_price' => 1500000,
                        'selling_price' => 1700000,
                        'stock' => 2,
                        'unit' => 'Pcs',
                    ]
                ]
            ],

            // Filter
            [
                'product' => [
                    'name' => 'Filter Oli Toyota Avanza',
                    'brand' => 'Toyota Genuine',
                    'description' => 'Filter oli original untuk Toyota Avanza',
                    'type_item_id' => $filterType->id,
                ],
                'items' => [
                    [
                        'name' => 'Standard',
                        'sku' => 'FLT-TY-001',
                        'purchase_price' => 45000,
                        'selling_price' => 55000,
                        'stock' => 25,
                        'unit' => 'Pcs',
                    ]
                ]
            ],
            [
                'product' => [
                    'name' => 'Filter Udara Honda Brio',
                    'brand' => 'Honda Genuine',
                    'description' => 'Filter udara original untuk Honda Brio',
                    'type_item_id' => $filterType->id,
                ],
                'items' => [
                    [
                        'name' => 'Standard',
                        'sku' => 'FLT-HN-001',
                        'purchase_price' => 35000,
                        'selling_price' => 45000,
                        'stock' => 20,
                        'unit' => 'Pcs',
                    ]
                ]
            ],

            // Rem & Kopling
            [
                'product' => [
                    'name' => 'Kampas Rem Depan Toyota Avanza',
                    'brand' => 'Toyota Genuine',
                    'description' => 'Kampas rem depan original untuk Toyota Avanza',
                    'type_item_id' => $remType->id,
                ],
                'items' => [
                    [
                        'name' => 'Set',
                        'sku' => 'KMP-TY-001',
                        'purchase_price' => 250000,
                        'selling_price' => 300000,
                        'stock' => 10,
                        'unit' => 'Set',
                    ]
                ]
            ],
            [
                'product' => [
                    'name' => 'Kampas Kopling Honda Brio',
                    'brand' => 'Honda Genuine',
                    'description' => 'Kampas kopling original untuk Honda Brio',
                    'type_item_id' => $remType->id,
                ],
                'items' => [
                    [
                        'name' => 'Set',
                        'sku' => 'KMP-HN-001',
                        'purchase_price' => 350000,
                        'selling_price' => 400000,
                        'stock' => 8,
                        'unit' => 'Set',
                    ]
                ]
            ],

            // Perlengkapan
            [
                'product' => [
                    'name' => 'Wiper Blade Toyota Avanza',
                    'brand' => 'Toyota Genuine',
                    'description' => 'Wiper blade original untuk Toyota Avanza',
                    'type_item_id' => $perlengkapanType->id,
                ],
                'items' => [
                    [
                        'name' => 'Set',
                        'sku' => 'WPR-TY-001',
                        'purchase_price' => 85000,
                        'selling_price' => 100000,
                        'stock' => 15,
                        'unit' => 'Set',
                    ]
                ]
            ],
            [
                'product' => [
                    'name' => 'Lampu Depan Honda Brio',
                    'brand' => 'Honda Genuine',
                    'description' => 'Lampu depan original untuk Honda Brio',
                    'type_item_id' => $perlengkapanType->id,
                ],
                'items' => [
                    [
                        'name' => 'Unit',
                        'sku' => 'LMP-HN-001',
                        'purchase_price' => 450000,
                        'selling_price' => 500000,
                        'stock' => 5,
                        'unit' => 'Pcs',
                    ]
                ]
            ],
        ];

        foreach ($productsData as $productData) {
            // Create Product
            $product = Product::create($productData['product']);

            // Create Items for this Product
            foreach ($productData['items'] as $itemData) {
                $itemData['product_id'] = $product->id;
                Item::create($itemData);
            }
        }
    }
}
