<?php

namespace App\Filament\Imports;

use App\Models\Item;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\TypeItem;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

class ItemImporter extends Importer
{
    protected static ?string $model = Item::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('product')
                ->label('Nama Produk')
                ->relationship(resolveUsing: 'name'),
            ImportColumn::make('name')
                ->label('Nama Varian'),
            ImportColumn::make('sku')
                ->label('SKU Varian')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('purchase_price')
                ->label('Harga Beli Varian')
                ->numeric(),
            ImportColumn::make('selling_price')
                ->label('Harga Jual Varian')
                ->numeric(),
            ImportColumn::make('stock')
                ->label('Stok Varian')
                ->numeric(),
            ImportColumn::make('minimum_stock')
                ->label('Stok Minimum')
                ->numeric(),
            ImportColumn::make('unit')
                ->label('Satuan Varian'),
            ImportColumn::make('volume_value')
                ->label('Nilai Volume')
                ->numeric(),
            ImportColumn::make('base_volume_unit')
                ->label('Satuan Volume Dasar'),
            ImportColumn::make('supplier')
                ->label('Supplier')
                ->relationship(resolveUsing: 'name'),
        ];
    }

    public function resolveRecord(): ?Item
    {
        return Item::firstOrNew([
            'sku' => $this->data['sku'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your item import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
