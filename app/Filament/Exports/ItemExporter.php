<?php

namespace App\Filament\Exports;

use App\Models\Item;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class ItemExporter extends Exporter
{
    protected static ?string $model = Item::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')
                ->label('Nama Produk'),
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

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your item export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with('items');
    }
}
