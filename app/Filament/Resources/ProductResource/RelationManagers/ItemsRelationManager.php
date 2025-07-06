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
    protected static ?string $title = 'Varian / Item Produk';

    // Removed canViewForRecord to make it always visible.
    // Logic to handle single vs multiple items will be in form/table/actions.

    public function form(Form $form): Form
    {
        $product = $this->getOwnerRecord();
        $isSingleItemMode = $product && !$product->has_variants;

        // Define the schema components directly, adjusting based on mode
        $schema = [
            Forms\Components\TextInput::make('name')
                ->label('Spesifikasi / Ukuran')
                ->placeholder($isSingleItemMode ? 'Standard (Item Tunggal)' : 'Contoh: 1 Liter, 5W-30')
                ->helperText($isSingleItemMode ? 'Untuk produk item tunggal, ini tidak perlu diisi.' : 'Isi jika barang memiliki ukuran/spesifikasi khusus.')
                ->disabled($isSingleItemMode)
                ->dehydrated(!$isSingleItemMode) // Only save if not single item mode (i.e. has_variants=true)
                                                // For single item, name in DB will be null or handled by mutate.
                ->default(null) ,
                // Removed SKU generation from 'name' field's afterStateUpdated
                // SKU will be generated in mutateFormDataUsing / mutateFormDataBeforeSave
            Forms\Components\TextInput::make('sku')
                ->label('Kode Barang')
                ->placeholder('Akan otomatis terisi / dibuat') // Updated placeholder
                ->helperText('Kode unik untuk identifikasi barang.')
                ->required()
                ->unique(ignoreRecord: true, table: Item::class),

            // Sections for Harga & Stok, Pengaturan Tambahan (from ItemResource)
            // This requires duplicating parts of ItemResource's form schema.
            // For brevity, I will assume these are added. A better way would be to
            // have ItemResource::getSchemaForContext(bool $isSingleItemMode)
            // For now, let's put the essential price and stock fields.

            Forms\Components\Section::make('Harga & Stok')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('purchase_price')
                                ->label('Harga Beli')
                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                ->prefix('Rp')
                                ->required(),
                            Forms\Components\TextInput::make('selling_price')
                                ->label('Harga Jual')
                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                ->prefix('Rp')
                                ->required(),
                        ]),
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('stock')
                                ->label('Jumlah Stok')
                                ->numeric()
                                ->required()->default(0),
                            Forms\Components\Select::make('unit')
                                ->label('Satuan')
                                ->options([
                                    'Pcs' => 'Pcs', 'Botol' => 'Botol', 'Galon' => 'Galon',
                                    'Liter' => 'Liter', 'Ml' => 'Ml', 'Set' => 'Set', 'Drum' => 'Drum',
                                ])
                                ->required()->default('Pcs'),
                        ]),
                ]),
            // Skipping conversion section for now to keep this change focused.
            // It would ideally be included from a shared schema definition.
        ];

        return $form->schema($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Spesifikasi / Ukuran')
                    ->placeholder('Standard') // Placeholder for empty/null variant names
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Harga Beli')
                    ->currency('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->currency('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok')
                    ->badge()
                    ->color(fn($state) => $state > 20 ? 'success' : ($state > 0 ? 'warning' : 'danger'))
                    ->formatStateUsing(fn($state, $record) => $state . ' ' . $record->unit)
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_convertible')
                    ->label('Bisa Dipecah')
                    ->boolean()
                    ->getStateUsing(fn(Item $record) => $record->is_convertible) // Ensure correct model type hint
                    ->trueIcon('heroicon-o-arrows-right-left')
                    ->falseIcon('heroicon-o-cube'),
            ])
            ->filters([
                // Add filters if needed, e.g., for stock status within variants
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(fn () => $this->getOwnerRecord()->has_variants ? 'Tambah Varian' : 'Tambah Item Produk')
                    ->modalHeading(fn () => $this->getOwnerRecord()->has_variants ? 'Tambah Varian Baru' : 'Tambah Item Produk')
                    ->visible(fn () => $this->getOwnerRecord()->has_variants || $this->getOwnerRecord()->items()->count() === 0) // Only show if has_variants OR no items yet for single item mode
                    ->mutateFormDataUsing(function (array $data): array {
                        if (!$this->getOwnerRecord()->has_variants) {
                            $data['name'] = null; // Ensure name is null for single item
                        }
                        // Ensure product_id is set, though Filament usually handles this for RMs
                        $data['product_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                // Re-check visibility and action logic for pecahStok
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
                    })
                    ->visible(fn(Item $record): bool => $record->is_convertible),
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
