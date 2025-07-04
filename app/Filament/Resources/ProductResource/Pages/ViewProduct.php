<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\RelationManagers\ItemsRelationManager;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('addVariant')
                ->label('Tambah Varian')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->url(fn($record) => route('filament.admin.resources.items.create', ['product_id' => $record->id]))
                ->tooltip('Tambah varian baru untuk produk ini'),
        ];
    }

    // getRelation
      public function getRelationManagers(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }


}
