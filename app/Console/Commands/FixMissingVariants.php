<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Item;
use Illuminate\Console\Command;

class FixMissingVariants extends Command
{
    protected $signature = 'fix:missing-variants';
    protected $description = 'Fix products with variants but no items';

    public function handle()
    {
        $this->info('Mencari produk dengan varian tapi tidak ada item...');
        
        $productsWithoutItems = Product::where('has_variants', true)
            ->whereDoesntHave('items')
            ->get();
            
        if ($productsWithoutItems->count() === 0) {
            $this->info('Tidak ada produk yang perlu diperbaiki.');
            return;
        }
        
        $this->info("Ditemukan {$productsWithoutItems->count()} produk yang perlu diperbaiki:");
        
        foreach ($productsWithoutItems as $product) {
            $this->line("- {$product->name}");
            
            // Create placeholder item
            Item::create([
                'product_id' => $product->id,
                'name' => 'Belum Ada Varian',
                'sku' => $this->generateDefaultSKU($product) . '-TEMP',
                'unit' => 'Pcs',
                'purchase_price' => 0,
                'selling_price' => 0,
                'stock' => 0,
                'target_child_item_id' => null,
                'conversion_value' => null,
            ]);
            
            $this->info("✅ Placeholder item dibuat untuk: {$product->name}");
        }
        
        $this->info('Semua produk berhasil diperbaiki!');
    }
    
    private function generateDefaultSKU($product): string
    {
        $productCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $product->name), 0, 6));
        return $productCode . '-STD';
    }
}
