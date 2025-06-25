<?php

namespace App\Filament\Resources\ItemResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Item; // Make sure Item model is imported

use Filament\Tables\Actions\AttachAction; // Import AttachAction

class ConversionChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'conversionChildren';

    protected static ?string $recordTitleAttribute = 'name';

    // This form is now primarily for the EditAction to edit pivot fields.
    // AttachAction will define its own form structure for selecting the record and pivot fields.
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // This field is not strictly needed here if child_item_id cannot be changed during Edit.
                // If you want to change WHICH child is linked during an Edit action on the pivot,
                // you might need a different setup or accept that Edit only changes pivot data.
                // For now, focusing on editing the conversion_value.
                // Forms\Components\Select::make('child_item_id')
                //     ->label('Child Item (Item Eceran)')
                //     ->options(Item::query()->pluck('name', 'id'))
                //     ->searchable()
                //     ->required() // Required if you allow changing it
                //     ->disabledOn('edit'), // Typically you don't change the related item in an edit of pivot

                Forms\Components\TextInput::make('conversion_value')
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
                // Tables\Actions\CreateAction::make(), // Replaced by AttachAction
                AttachAction::make()
                    // ->preloadRecordSelect() // May not be needed or work the same with manual Select
                    ->form(fn (RelationManager $livewire): array => [ // Removed AttachAction $action from params for this test
                        // --- Replace $action->getRecordSelect() ---
                        Forms\Components\Select::make('recordId') // Default key Filament expects for the selected record ID
                            ->label('Pilih Item Turunan (Eceran)')
                            ->helperText('Pilih item yang sudah ada untuk dijadikan turunan/eceran.')
                            ->options(Item::query()
                                ->where('id', '!=', $livewire->ownerRecord->getKey()) // Exclude the parent itself
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required(), // Make sure it's required
                        // --- End of replacement ---
                        Forms\Components\TextInput::make('conversion_value')
                            ->label('Nilai Konversi')
                            ->numeric()
                            ->required(),
                    ])
            ])
            ->actions([
                Tables\Actions\EditAction::make(), // Uses the main form() method by default
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
