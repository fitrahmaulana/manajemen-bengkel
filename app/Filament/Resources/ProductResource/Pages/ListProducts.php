<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Exports\ProductExporter;
use App\Filament\Imports\ProductImporter;
use Filament\Actions\ImportAction;
use Filament\Actions\ExportAction;


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
                ->importer(ProductImporter::class),
            ExportAction::make()
                ->exporter(ProductExporter::class),
        ];
    }
}
