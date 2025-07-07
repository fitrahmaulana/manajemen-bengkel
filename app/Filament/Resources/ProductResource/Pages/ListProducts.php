<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Produk')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->tooltip('Tambah produk baru')
                ->modalHeading('Tambah Produk Baru')
                ->modalSubmitActionLabel('Simpan Produk')
                ->successNotificationTitle('Produk berhasil ditambahkan'),
        ];
    }
}
