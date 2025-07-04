<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Item;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getRedirectUrl(): string
    {
        // Redirect ke halaman daftar produk setelah create
        return $this->getResource()::getUrl('view', [
            'record' => $this->getRecord(),
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Simpan data form original untuk Observer via session
        session(['product_form_data' => $data]);

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
        // Observer sudah handle pembuatan item default
        // Cleanup session data jika masih ada
        session()->forget('product_form_data');
    }
}
