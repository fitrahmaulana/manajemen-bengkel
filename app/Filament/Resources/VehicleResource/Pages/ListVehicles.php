<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicles extends ListRecords
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kendaraan')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->tooltip('Tambah kendaraan baru')
                ->modalHeading('Tambah Kendaraan Baru')
                ->modalSubmitActionLabel('Simpan Kendaraan')
                ->successNotificationTitle('Kendaraan berhasil ditambahkan'),
        ];
    }
}
