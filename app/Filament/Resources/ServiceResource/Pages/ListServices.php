<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServices extends ListRecords
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Layanan')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->tooltip('Tambah layanan baru')
                ->modalHeading('Tambah Layanan Baru')
                ->modalSubmitActionLabel('Simpan Layanan')
                ->successNotificationTitle('Layanan berhasil ditambahkan'),
        ];
    }
}
