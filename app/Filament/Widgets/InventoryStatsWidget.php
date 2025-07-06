<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalItems = Item::whereHas('product')->count();
        $lowStockItems = Item::whereHas('product')->where('stock', '>', 0)->where('stock', '<=', 5)->count();
        $outOfStockItems = Item::whereHas('product')->where('stock', '<=', 0)->count();
        $totalStockValue = Item::whereHas('product')->selectRaw('SUM(stock * selling_price)')->value('sum') ?? 0;

        return [
            Stat::make('Total Barang', $totalItems)
                ->description('Semua varian produk')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Stok Kritis', $lowStockItems)
                ->description('Barang dengan stok ≤ 5')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning')
                ->url(route('filament.admin.resources.inventory.index', ['tableFilters[stock_status][stock_type][]' => 'critical'])),

            Stat::make('Habis Stok', $outOfStockItems)
                ->description('Barang yang habis')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->url(route('filament.admin.resources.inventory.index', ['tableFilters[stock_status][stock_type][]' => 'out_of_stock'])),

            Stat::make('Nilai Stok', 'Rp ' . number_format($totalStockValue, 0, ',', '.'))
                ->description('Total nilai semua stok')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }

    protected static ?string $pollingInterval = '30s';
}
