<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Item;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getRedirectUrl(): string
    {
        // Redirect ke halaman detail produk setelah edit
        return $this->getResource()::getUrl('view', [
            'record' => $this->getRecord(),
        ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $product = $this->record;

        // Load has_variants dari database
        $data['has_variants'] = $product->has_variants;

        // Load existing items untuk pre-fill form
        if (! $product->has_variants) {
            $item = $product->items->first();
            if ($item) {
                $data['standard_sku'] = $item->sku;
                $data['standard_unit'] = $item->unit;
                $data['standard_purchase_price'] = $item->purchase_price;
                $data['standard_selling_price'] = $item->selling_price;
                $data['standard_stock'] = $item->stock;
            }
        }

        return $data;
    }

    protected array $originalFormData = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Simpan data form original untuk handleRecordUpdate
        $this->originalFormData = $data;

        // Remove form fields yang tidak ada di tabel products
        unset($data['standard_sku']);
        unset($data['standard_unit']);
        unset($data['standard_purchase_price']);
        unset($data['standard_selling_price']);
        unset($data['standard_stock']);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Refactor: gunakan transaction untuk update product dan item
        try {
            DB::transaction(function () use ($record, $data) {
                $record->update($data);
                $this->handleItemsAfterProductUpdate($record);
            });

            return $record;
        } catch (\Exception $e) {
            // Tampilkan notifikasi error ke user
            Notification::make()
                ->title('Gagal menyimpan data')
                ->body('Terjadi kesalahan: '.$e->getMessage())
                ->danger()
                ->send();
            throw $e;
        }
    }

    private function handleItemsAfterProductUpdate($product): void
    {
        $data = $this->originalFormData; // Gunakan data form original

        // Auto-generate product code untuk SKU
        $productCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $product->name), 0, 6));

        if (isset($data['has_variants']) && $data['has_variants']) {
            // Produk dengan varian - varian akan dikelola melalui RelationManager
            // Hapus hanya item default produk tunggal (name kosong)
            $product->items()->where('name', '')->delete();
        } else {
            // Produk tanpa varian - buat atau update single item
            // Hapus semua items lama dulu
            $product->items()->delete();

            $sku = $data['standard_sku'] ?: ($productCode.'-STD');

            Item::create([
                'product_id' => $product->id,
                'name' => '', // Empty string untuk produk standard
                'sku' => $sku,
                'unit' => $data['standard_unit'] ?? 'Pcs',
                'purchase_price' => $data['standard_purchase_price'] ?? 0,
                'selling_price' => $data['standard_selling_price'] ?? 0,
                'stock' => $data['standard_stock'] ?? 0,
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
