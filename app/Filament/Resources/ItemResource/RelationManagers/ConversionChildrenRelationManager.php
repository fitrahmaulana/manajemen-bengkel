<?php

namespace App\Filament\Resources\ItemResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Item;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\Action; // Added for the new header action
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder; // Required for modifying query in Select options

class ConversionChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'conversionChildren';
    protected static ?string $recordTitleAttribute = 'name';

    // This form is used by EditAction for the pivot data
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('conversion_value')
                    ->label('Nilai Konversi')
                    ->numeric()
                    ->required()
                    ->gt(0)
                    ->helperText('Jumlah unit item turunan yang dihasilkan dari 1 unit item induk.'),
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
                    ->label('Lampirkan Item Eceran Ada')
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action, RelationManager $livewire): array => [
                        $action->getRecordSelect()
                            ->label('Pilih Item Turunan (Eceran)')
                            ->helperText('Pilih item yang sudah ada untuk dijadikan turunan/eceran.')
                            ->options(function(RelationManager $livewire) { // Closure to get options
                                return Item::query()
                                    ->where('id', '!=', $livewire->ownerRecord->getKey()) // Exclude the parent itself
                                    ->where(function ($query) { // Exclude items already linked as children to this parent
                                        $query->whereNotIn('id', $livewire->ownerRecord->conversionChildren()->pluck('items.id')->toArray());
                                    })
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required(),
                        TextInput::make('conversion_value')
                            ->label('Nilai Konversi (Induk ke Eceran ini)')
                            ->numeric()
                            ->required()
                            ->gt(0),
                    ]),
                Action::make('createAndAttachEceran')
                    ->label('Buat & Lampirkan Eceran Baru')
                    ->icon('heroicon-o-plus-circle')
                    ->form([
                        TextInput::make('name')
                            ->label('Nama Item Eceran Baru')
                            ->default(fn (RelationManager $livewire): string => $livewire->ownerRecord->name . ' (Eceran)')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('sku')
                            ->label('SKU Item Eceran Baru')
                            ->default(fn (RelationManager $livewire): string => $livewire->ownerRecord->sku . '-ECER')
                            ->required()
                            ->maxLength(255)
                            ->unique(table: Item::class, column: 'sku', ignoreRecord: true),
                        TextInput::make('unit')
                            ->label('Satuan Item Eceran')
                            ->helperText('Contoh: Liter, Pcs, Ml')
                            ->required()
                            ->default('Liter')
                            ->maxLength(50),
                        TextInput::make('selling_price')
                            ->label('Harga Jual Eceran')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->default(0),
                        TextInput::make('purchase_price')
                            ->label('Harga Beli Eceran (Modal)')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->default(0),
                        TextInput::make('type_item_id') // Assuming type_item_id is needed
                            ->label('Tipe Item ID (Jika ada)')
                            ->numeric()
                            ->nullable(), // Make it nullable if it's optional
                        TextInput::make('conversion_value_pivot') // Specific name for pivot field
                            ->label('Nilai Konversi (Induk ke Eceran ini)')
                            ->numeric()
                            ->required()
                            ->gt(0)
                            ->helperText("Contoh: Jika Induk adalah 'Oli 4L' dan eceran ini dalam Liter, maka nilai konversi adalah 4."),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        DB::transaction(function () use ($data, $livewire) {
                            $eceranItem = Item::create([
                                'name' => $data['name'],
                                'sku' => $data['sku'],
                                'unit' => $data['unit'],
                                'selling_price' => $data['selling_price'],
                                'purchase_price' => $data['purchase_price'],
                                'stock' => 0,
                                'is_convertible' => false,
                                'type_item_id' => $data['type_item_id'] ?? null, // Handle if optional
                            ]);

                            $livewire->ownerRecord->conversionChildren()->attach($eceranItem->id, [
                                'conversion_value' => $data['conversion_value_pivot']
                            ]);

                            Notification::make()
                                ->title('Item Eceran Baru Dibuat & Dilampirkan')
                                ->success()
                                ->send();
                        });
                        $livewire->dispatch('refresh'); // Standard Filament event to refresh components
                    })
                    ->modalWidth('xl'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(), // Edits the pivot data (conversion_value)
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
