<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Item;

class TestSearchQuery extends Command
{
    protected $signature = 'test:search-query';
    protected $description = 'Test search query untuk ProductResource';

    public function handle()
    {
        $this->info('Testing search query...');

        try {
            // Query yang sama dengan ProductResource
            $query = Item::query()
                ->join('products', 'items.product_id', '=', 'products.id')
                ->join('type_items', 'products.type_item_id', '=', 'type_items.id')
                ->with(['product', 'product.typeItem'])
                ->select('items.*');

            // Test basic query
            $count = $query->count();
            $this->info("Total items found: {$count}");

            // Test search query
            $searchTerm = 'oli'; // Contoh search term
            $searchQuery = clone $query;
            $searchResults = $searchQuery->where(function ($q) use ($searchTerm) {
                $q->where('products.name', 'like', "%{$searchTerm}%")
                  ->orWhere('products.brand', 'like', "%{$searchTerm}%")
                  ->orWhere('items.name', 'like', "%{$searchTerm}%")
                  ->orWhere('type_items.name', 'like', "%{$searchTerm}%");
            })->get();

            $this->info("Search results for '{$searchTerm}': {$searchResults->count()} items");

            // Display some results
            foreach ($searchResults->take(3) as $item) {
                $productName = $item->product->name;
                $variant = $item->name;
                $category = $item->product->typeItem->name;
                
                $displayName = $variant && $variant !== 'Belum Ada Varian' 
                    ? "{$productName} - {$variant}" 
                    : $productName;
                
                $this->line("- {$displayName} ({$category})");
            }

            $this->success('Search query test completed successfully!');

        } catch (\Exception $e) {
            $this->error("Error in search query: " . $e->getMessage());
            $this->error("SQL: " . $e->getTraceAsString());
        }
    }
}
