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
            ImportColumn::make('product_name')
                ->label('Nama Produk')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('product_brand')
                ->label('Merek'),
            ImportColumn::make('product_description')
                ->label('Deskripsi'),
            ImportColumn::make('product_type_item_name')
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
            ImportColumn::make('supplier.name')
                ->label('Supplier'),
        ];
    }

    public function resolveRecord(): ?Item
    {
        $product = Product::updateOrCreate(
            ['name' => $this->data['product_name']],
            [
                'brand' => $this->data['product_brand'],
                'description' => $this->data['product_description'],
                'type_item_id' => TypeItem::firstOrCreate(['name' => $this->data['product_type_item_name']])->id,
                'has_variants' => true,
            ]
        );

        $supplier = null;
        if (!empty($this->data['supplier.name'])) {
            $supplier = Supplier::updateOrCreate(['name' => $this->data['supplier.name']]);
        }

        $item = Item::updateOrCreate(
            ['sku' => $this->data['sku']],
            [
                'product_id' => $product->id,
                'name' => $this->data['name'],
                'purchase_price' => $this->data['purchase_price'],
                'selling_price' => $this->data['selling_price'],
                'stock' => $this->data['stock'],
                'minimum_stock' => $this->data['minimum_stock'],
                'unit' => $this->data['unit'],
                'volume_value' => $this->data['volume_value'],
                'base_volume_unit' => $this->data['base_volume_unit'],
                'supplier_id' => $supplier?->id,
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
