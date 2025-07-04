<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Item;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected array $originalFormData = [];

    protected function getRedirectUrl(): string
    {
        // Redirect ke halaman daftar produk setelah create
        return $this->getResource()::getUrl('edit', [
            'record' => $this->getRecord(),
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Simpan data form original untuk afterCreate
        $this->originalFormData = $data;

        // Remove form fields yang tidak ada di tabel products
        unset($data['standard_sku']);
        unset($data['standard_unit']);
        unset($data['standard_purchase_price']);
        unset($data['standard_selling_price']);
        unset($data['standard_stock']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->originalFormData; // Gunakan data form original
        $product = $this->record;

        // Auto-generate product code untuk SKU
        $productCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $product->name), 0, 6));

        if (isset($data['has_variants']) && $data['has_variants']) {
            // Produk dengan varian - varian akan dikelola melalui RelationManager
            // Tidak perlu membuat Item di sini
        } else {
            // Produk tanpa varian - buat single item
            $sku = !empty($data['standard_sku']) ? $data['standard_sku'] : ($productCode . '-STD');

            Item::create([
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $sku,
                'unit' => $data['standard_unit'] ?? 'Pcs',
                'purchase_price' => $data['standard_purchase_price'] ?? 0,
                'selling_price' => $data['standard_selling_price'] ?? 0,
                'stock' => $data['standard_stock'] ?? 0,
            ]);
        }
    }
}
