<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\RelationManagers\ItemsRelationManager;
use Filament\Actions;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Ubah Produk')->icon('heroicon-o-pencil'),
            Actions\DeleteAction::make()
                ->label('Hapus Produk')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->successNotificationTitle('Produk berhasil dihapus')
                ->requiresConfirmation(fn ($record) => $record->items->count() > 0)
                ->modalDescription('Produk ini memiliki varian. Apakah Anda yakin ingin menghapusnya?')
                ->successNotificationTitle('Produk dan semua variannya telah dihapus.'),
        ];
    }

    public function getRelationManagers(): array
    {
        // Hanya tampilkan ItemsRelationManager jika produk memiliki varian
        if ($this->record->has_variants) {
            return [
                ItemsRelationManager::class,
            ];
        }

        return [];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Informasi Produk')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label('Nama Produk'),
                        TextEntry::make('brand')->label('Merek'),
                        TextEntry::make('typeItem.name')->label('Kategori'),
                        TextEntry::make('has_variants')
                            ->label('Jenis Produk')
                            ->getStateUsing(fn ($record) => $record->has_variants ? 'Memiliki Varian' : 'Produk Tunggal'),
                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                    ]),

                InfolistSection::make('Statistik')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('items_count')
                            ->label('Jumlah Varian')
                            ->getStateUsing(fn ($record) => $record->items->count() > 0 ? $record->items->count() : '-'),
                        TextEntry::make('total_stock')
                            ->label('Total Stok')
                            ->getStateUsing(fn ($record) => $record->items->sum('stock')),
                        TextEntry::make('average_price')
                            ->label('Rata-rata Harga')
                            ->getStateUsing(fn ($record) => 'Rp '.number_format($record->items->avg('selling_price'), 0, ',', '.')),
                    ]),
            ]);
    }
}
