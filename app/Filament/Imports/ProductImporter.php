<?php

namespace App\Filament\Imports;

use App\Models\Product;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Nama Produk')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('brand')
                ->label('Merek'),
            ImportColumn::make('description')
                ->label('Deskripsi'),
            ImportColumn::make('typeItem')
                ->label('Kategori Produk')
                ->relationship(resolveUsing: 'name'),
            ImportColumn::make('has_variants')
                ->label('Memiliki Varian')
                ->boolean(),
            ImportColumn::make('item_name')
                ->label('Nama Varian'),
            ImportColumn::make('item_sku')
                ->label('SKU Varian'),
            ImportColumn::make('item_purchase_price')
                ->label('Harga Beli Varian')
                ->numeric(),
            ImportColumn::make('item_selling_price')
                ->label('Harga Jual Varian')
                ->numeric(),
            ImportColumn::make('item_stock')
                ->label('Stok Varian')
                ->numeric(),
            ImportColumn::make('item_unit')
                ->label('Satuan Varian'),
        ];
    }

    public function resolveRecord(): ?Product
    {
        $product = Product::firstOrNew([
            'name' => $this->data['name'],
        ]);

        if ($product->exists) {
            $this->handleRecordUpdate($product);
        }

        return $product;
    }

    protected function afterSave(): void
    {
        if ($this->record->has_variants && !empty($this->data['item_name'])) {
            $this->record->items()->create([
                'name' => $this->data['item_name'],
                'sku' => $this->data['item_sku'],
                'purchase_price' => $this->data['item_purchase_price'],
                'selling_price' => $this->data['item_selling_price'],
                'stock' => $this->data['item_stock'],
                'unit' => $this->data['item_unit'],
            ]);
        }
    }

    public static function getCompletedNotificationBody(): string
    {
        return 'Your product import has completed! We have imported ' . number_format($this->getCompletedRecordsCount()) . ' records.';
    }
}
