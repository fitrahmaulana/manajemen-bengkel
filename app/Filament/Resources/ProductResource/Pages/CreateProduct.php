<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Item;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected array $standardData = [];

    protected function getRedirectUrl(): string
    {
        // Redirect ke halaman daftar produk setelah create
        return $this->getResource()::getUrl('view', [
            'record' => $this->getRecord(),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Simpan data standard untuk digunakan nanti
        $this->standardData = [
            'standard_sku' => $data['standard_sku'] ?? null,
            'standard_unit' => $data['standard_unit'] ?? 'Pcs',
            'standard_purchase_price' => $data['standard_purchase_price'] ?? 0,
            'standard_selling_price' => $data['standard_selling_price'] ?? 0,
            'standard_stock' => $data['standard_stock'] ?? 0,
        ];

        // Remove form fields yang tidak ada di tabel products
        unset($data['standard_sku']);
        unset($data['standard_unit']);
        unset($data['standard_purchase_price']);
        unset($data['standard_selling_price']);
        unset($data['standard_stock']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Buat product terlebih dahulu
        $product = Product::create($data);

        // Langsung buat item berdasarkan jenis product
        if (! $product->has_variants) {
            // Produk standard - gunakan data dari standardData
            $this->createDefaultItem($product);
        }

        return $product;
    }

    /**
     * Create default item for non-variant product
     */
    private function createDefaultItem($product): void
    {
        Item::create([
            'product_id' => $product->id,
            'name' => null, // null untuk produk standard
            'sku' => $this->standardData['standard_sku'] ?? $this->generateDefaultSKU($product),
            'unit' => $this->standardData['standard_unit'] ?? 'Pcs',
            'purchase_price' => $this->standardData['standard_purchase_price'] ?? 0,
            'selling_price' => $this->standardData['standard_selling_price'] ?? 0,
            'stock' => $this->standardData['standard_stock'] ?? 0,
        ]);
    }

    /**
     * Generate default SKU if not provided
     */
    private function generateDefaultSKU($product): string
    {
        $productCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $product->name), 0, 6));

        return $productCode.'-STD';
    }
}
