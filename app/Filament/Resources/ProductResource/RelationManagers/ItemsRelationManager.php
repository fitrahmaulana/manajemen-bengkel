<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Filament\Resources\ItemResource;
use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $title = 'Daftar Varian';

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->has_variants;
    }



    public function form(Form $form): Form
    {
        return ItemResource::form($form);
    }

    public function table(Table $table): Table
    {
        return ItemResource::table($table)
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Varian')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->tooltip('Tambah varian baru')
                    ->modalHeading('Tambah Varian Baru')
                    ->modalSubmitActionLabel('Simpan Varian')
                    ->successNotificationTitle('Varian berhasil ditambahkan'),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
