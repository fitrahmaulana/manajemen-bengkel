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
                Tables\Columns\IconColumn::make('is_convertible')
                    ->label('Bisa Dipecah')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->is_convertible),
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
                    ->label('Pecah Stok')
                    ->icon('heroicon-o-arrows-right-left')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pecah Stok')
                    ->modalDescription('Apakah Anda yakin ingin memecah 1 unit dari varian ini?')
                    ->modalSubmitActionLabel('Ya, Pecah')
                    ->action(function (Item $record) {
                        $sourceItem = $record;
                        $targetItem = $sourceItem->targetChild;

                        if (!$targetItem) {
                            \Filament\Notifications\Notification::make()
                                ->title('Proses Gagal')
                                ->body('Target item eceran belum diatur.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if (!$sourceItem->conversion_value || $sourceItem->conversion_value <= 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('Proses Gagal')
                                ->body("Nilai konversi tidak valid.")
                                ->danger()
                                ->send();
                            return;
                        }

                        if ($sourceItem->stock < 1) {
                            \Filament\Notifications\Notification::make()
                                ->title('Proses Gagal')
                                ->body("Stok tidak mencukupi.")
                                ->danger()
                                ->send();
                            return;
                        }

                        try {
                            \Illuminate\Support\Facades\DB::transaction(function () use ($sourceItem, $targetItem) {
                                $sourceItem->decrement('stock', 1);
                                $targetItem->increment('stock', $sourceItem->conversion_value);
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil Pecah Stok')
                                ->success()
                                ->body("Stok berhasil dipecah.")
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Proses Gagal')
                                ->body('Terjadi kesalahan internal.')
                                ->danger()
                                ->send();
                        }
                    })->visible(fn(Item $record): bool => $record->is_convertible ?? false),

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
