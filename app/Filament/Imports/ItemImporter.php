<?php

namespace App\Filament\Imports;

use App\Models\Item;
use App\Models\Product;
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
            ImportColumn::make('product.name')
                ->label('Nama Produk')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('product.brand')
                ->label('Merek'),
            ImportColumn::make('product.description')
                ->label('Deskripsi'),
            ImportColumn::make('product.typeItem.name')
                ->label('Kategori Produk'),
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
            ImportColumn::make('unit')
                ->label('Satuan Varian'),
        ];
    }

    public function resolveRecord(): ?Item
    {
        $product = Product::firstOrCreate(
            ['name' => $this->data['product.name']],
            [
                'brand' => $this->data['product.brand'],
                'description' => $this->data['product.description'],
                'type_item_id' => TypeItem::firstOrCreate(['name' => $this->data['product.typeItem.name']])->id,
                'has_variants' => true,
            ]
        );

        $item = Item::firstOrNew(
            ['sku' => $this->data['sku']],
            [
                'product_id' => $product->id,
                'name' => $this->data['name'],
                'purchase_price' => $this->data['purchase_price'],
                'selling_price' => $this->data['selling_price'],
                'stock' => $this->data['stock'],
                'unit' => $this->data['unit'],
            ]
        );

        return $item;
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
