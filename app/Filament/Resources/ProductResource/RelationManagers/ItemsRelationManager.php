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
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Spesifikasi')
                    ->formatStateUsing(fn(?string $state): string => empty($state) ? 'Standard' : $state),
                Tables\Columns\TextColumn::make('sku')
                    ->label('Kode Barang'),
                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->currency('IDR'),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok')
                    ->badge()
                    ->color(fn($state) => $state <= 5 ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Satuan'),
                Tables\Columns\IconColumn::make('has_conversions')
                    ->label('Bisa Dikonversi')
                    ->boolean()
                    ->getStateUsing(function ($record) {
                        // Periksa apakah item ini memiliki konfigurasi konversi sebagai source atau target
                        return \App\Models\ItemStockConversion::where('from_item_id', $record->id)
                            ->orWhere('to_item_id', $record->id)
                            ->exists();
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Varian')
                    ->modalHeading('Tambah Varian Baru'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('pecahStok')
                    ->label('Konversi Stok')
                    ->icon('heroicon-o-arrows-right-left')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Konversi Stok')
                    ->modalDescription('Fitur ini menggunakan sistem konversi baru. Silakan gunakan menu "Konversi Stok" di halaman Item untuk melakukan konversi.')
                    ->modalSubmitActionLabel('OK')
                    ->action(function (Item $record) {
                        \Filament\Notifications\Notification::make()
                            ->title('Info')
                            ->body('Silakan gunakan fitur "Konversi Stok" di halaman detail item untuk melakukan konversi dengan sistem yang baru.')
                            ->info()
                            ->send();
                    })
                    ->visible(function (Item $record): bool {
                        // Tampilkan jika item ini memiliki konfigurasi konversi
                        return \App\Models\ItemStockConversion::where('from_item_id', $record->id)
                            ->orWhere('to_item_id', $record->id)
                            ->exists();
                    }),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
