<?php

namespace App\Filament\Imports;

use App\Models\Product;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

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
        return Product::firstOrNew([
            'name' => $this->data['name'],
        ]);
    }

    protected function afterSave(): void
    {
        if ($this->record->has_variants && !empty($this->data['item_name'])) {
            $this->record->items()->updateOrCreate(
                [
                    'sku' => $this->data['item_sku']
                ],
                [
                    'name' => $this->data['item_name'],
                    'purchase_price' => $this->data['item_purchase_price'],
                    'selling_price' => $this->data['item_selling_price'],
                    'stock' => $this->data['item_stock'],
                    'unit' => $this->data['item_unit'],
                ]
            );
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
