<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Exports\ItemExporter;
use App\Filament\Imports\ItemImporter;
use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
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
            ImportAction::make()
                ->importer(ItemImporter::class),
            ExportAction::make()
                ->exporter(ItemExporter::class),
        ];
    }
}
