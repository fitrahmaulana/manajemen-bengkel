<?php

namespace App\Filament\Exports;

use App\Models\Product;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\ExportColumn;

class ProductExporter extends Exporter
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')
                ->label('Nama Produk'),
            ExportColumn::make('brand')
                ->label('Merek'),
            ExportColumn::make('description')
                ->label('Deskripsi'),
            ExportColumn::make('typeItem.name')
                ->label('Kategori Produk'),
            ExportColumn::make('has_variants')
                ->label('Memiliki Varian'),
            ExportColumn::make('items.name')
                ->label('Nama Varian'),
            ExportColumn::make('items.sku')
                ->label('SKU Varian'),
            ExportColumn::make('items.purchase_price')
                ->label('Harga Beli Varian'),
            ExportColumn::make('items.selling_price')
                ->label('Harga Jual Varian'),
            ExportColumn::make('items.stock')
                ->label('Stok Varian'),
            ExportColumn::make('items.unit')
                ->label('Satuan Varian'),
        ];
    }

    public static function getCompletedNotificationBody(): string
    {
        return 'Your product export has completed! We have exported ' . number_format($this->getRecordsCount()) . ' records.';
    }
}
