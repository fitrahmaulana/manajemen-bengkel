<?php

namespace App\Filament\Resources\ItemResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Item;
use Filament\Tables\Actions\AttachAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class ConversionChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'conversionChildren';
    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('conversion_value')
                    ->label('Nilai Konversi')
                    ->numeric()
                    ->required()
                    ->helperText('Jumlah unit item turunan yang dihasilkan dari 1 unit item induk. Cth: Induk Oli 4L, Anak Oli Eceran (Liter), Nilai Konversi = 4.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Item Turunan (Eceran)'),
                Tables\Columns\TextColumn::make('pivot.conversion_value')
                    ->label('Nilai Konversi')
                    ->numeric(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->form(fn (RelationManager $livewire): array => [
                        Select::make('recordId') // Default key Filament expects for the selected record ID
                            ->label('Pilih Item Turunan (Eceran)')
                            ->helperText('Pilih item yang sudah ada atau buat baru di bawah.')
                            ->options(Item::query()
                                ->where('id', '!=', $livewire->ownerRecord->getKey()) // Exclude the parent itself
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->createOptionModalHeading('Buat Item Eceran Baru')
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nama Item Eceran Baru')
                                    ->default(fn () => $livewire->ownerRecord->name . ' (Eceran)')
                                    ->required(),
                                TextInput::make('sku')
                                    ->label('SKU Item Eceran Baru')
                                    ->default(fn () => $livewire->ownerRecord->sku . '-ECER')
                                    ->required()
                                    ->unique(table: Item::class, column: 'sku', ignoreRecord: true),
                                TextInput::make('unit')
                                    ->label('Satuan')
                                    ->required()
                                    ->default('Liter') // Example default, adjust as needed
                                    ->helperText('Contoh: Liter, Pcs, Ml'),
                                TextInput::make('selling_price')
                                    ->label('Harga Jual')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->default(0),
                                TextInput::make('purchase_price')
                                    ->label('Harga Beli (Modal)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->default(0),
                                // is_convertible will default to false in the DB or model
                                // stock will default to 0
                            ])
                            ->createOptionAction(function (array $data) {
                                $newItem = Item::create([
                                    'name' => $data['name'],
                                    'sku' => $data['sku'],
                                    'unit' => $data['unit'],
                                    'selling_price' => $data['selling_price'],
                                    'purchase_price' => $data['purchase_price'],
                                    'stock' => 0,
                                    'is_convertible' => false, // Explicitly set for clarity
                                ]);
                                return $newItem->id;
                            }),
                        TextInput::make('conversion_value')
                            ->label('Nilai Konversi (Induk ke Eceran ini)')
                            ->numeric()
                            ->required()
                            ->gt(0),
                    ])
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
