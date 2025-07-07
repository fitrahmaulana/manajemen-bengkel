<?php

namespace App\Filament\Resources\TypeItemResource\Pages;

use App\Filament\Resources\TypeItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTypeItems extends ListRecords
{
    protected static string $resource = TypeItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kategori')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->tooltip('Tambah kategori baru')
                ->modalHeading('Tambah Kategori Baru')
                ->modalSubmitActionLabel('Simpan Kategori')
                ->successNotificationTitle('Kategori berhasil ditambahkan'),
        ];
    }
}
