<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventory extends ListRecords
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refreshData')
                ->label('🔄 Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function () {
                    $this->resetTable();
                    \Filament\Notifications\Notification::make()
                        ->title('Data berhasil di-refresh')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('printInventory')
                ->label('🖨️ Print Inventory')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url('#')
                ->openUrlInNewTab(),

            Actions\Action::make('quickSearch')
                ->label('🔍 Pencarian Cepat')
                ->icon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->keyBindings(['cmd+k', 'ctrl+k'])
                ->action(function () {
                    // This will focus the search input
                    $this->dispatch('focus-search');
                }),
        ];
    }

    public function getTitle(): string
    {
        return '🛒 Inventory Kasir';
    }

    public function getSubheading(): ?string
    {
        $totalItems = $this->getTableQuery()->count();
        $lowStockItems = $this->getTableQuery()->where('stock', '<=', 5)->count();
        $outOfStockItems = $this->getTableQuery()->where('stock', '<=', 0)->count();

        return "Total: {$totalItems} barang | Stok rendah: {$lowStockItems} | Habis: {$outOfStockItems}";
    }
}
