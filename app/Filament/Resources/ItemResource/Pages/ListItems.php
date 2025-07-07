<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListItems extends ListRecords
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label('Tambah Item')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->tooltip('Tambah item baru')
                ->modalHeading('Tambah Item Baru')
                ->modalSubmitActionLabel('Simpan Item')
                ->successNotificationTitle('Item berhasil ditambahkan'),
        ];
    }
}
