<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use App\Filament\Resources\ItemResource\RelationManagers\ConversionChildrenRelationManager;
use App\Models\Item;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class EditItem extends EditRecord
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('buatDanLampirkanEceran')
                ->label('Buat & Lampirkan Item Eceran')
                ->icon('heroicon-o-plus-circle')
                ->visible(fn (): bool => $this->record->is_convertible && !$this->record->conversionChildren()->exists())
                ->form([
                    TextInput::make('eceran_name')
                        ->label('Nama Item Eceran')
                        ->default(fn (): string => $this->record->name . ' (Eceran)')
                        ->required(),
                    TextInput::make('eceran_sku')
                        ->label('SKU Item Eceran')
                        ->default(fn (): string => $this->record->sku . '-ECER')
                        ->required()
                        ->unique(table: Item::class, column: 'sku', ignoreRecord: true),
                    TextInput::make('eceran_unit')
                        ->label('Satuan Item Eceran')
                        ->helperText('Contoh: Liter, Pcs, Ml')
                        ->required(),
                    TextInput::make('eceran_selling_price')
                        ->label('Harga Jual Eceran')
                        ->numeric()
                        ->prefix('Rp')
                        ->required(),
                    TextInput::make('eceran_purchase_price')
                        ->label('Harga Beli Eceran (Modal)')
                        ->numeric()
                        ->prefix('Rp')
                        ->required(),
                    TextInput::make('conversion_value')
                        ->label('Nilai Konversi dari Induk ke Eceran ini')
                        ->numeric()
                        ->required()
                        ->gt(0)
                        ->helperText("Contoh: Jika Induk adalah 'Oli 4L' dan eceran ini dalam Liter, maka nilai konversi adalah 4."),
                ])
                ->action(function (array $data) {
                    DB::transaction(function () use ($data) {
                        // Create the new eceran item
                        $eceranItem = Item::create([
                            'name' => $data['eceran_name'],
                            'sku' => $data['eceran_sku'],
                            'unit' => $data['eceran_unit'],
                            'selling_price' => $data['eceran_selling_price'],
                            'purchase_price' => $data['eceran_purchase_price'],
                            'stock' => 0, // Initial stock for eceran item
                            'is_convertible' => false, // Eceran item itself is not convertible by default
                            // Add other necessary fields if any, e.g., type_item_id if required
                        ]);

                        // Attach the new eceran item to the parent (current record)
                        $this->record->conversionChildren()->attach($eceranItem->id, [
                            'conversion_value' => $data['conversion_value']
                        ]);

                        Notification::make()
                            ->title('Item Eceran Berhasil Dibuat dan Dilampirkan')
                            ->success()
                            ->send();

                        // Optionally, refresh relation manager or page
                        // $this->dispatch('refreshRelationManager', 'conversionChildren'); // Needs Livewire component event
                        // Or simply redirect to refresh data
                        // return redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));

                    });
                    // Refresh the data on the page to show the new relation if not using livewire refresh
                    $this->refreshFormData(['conversionChildren']);
                    // This might not auto-refresh a relation manager immediately without more specific livewire handling.
                    // A full page redirect or specific event listening in RM might be needed for instant RM refresh.
                })
                ->modalWidth('xl'), // Make modal wider for more fields
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Conditionally load relation managers.
     */
    public function getRelationManagers(): array
    {
        $managers = parent::getRelationManagers(); // Get default managers if any

        if ($this->record && $this->record->is_convertible) {
            // Add ConversionChildrenRelationManager if the item is convertible
            $managers[] = ConversionChildrenRelationManager::class;
        } else {
            // Ensure it's not loaded if not convertible, by filtering it out if it was somehow added by default
            $managers = array_filter($managers, function ($managerClass) {
                return $managerClass !== ConversionChildrenRelationManager::class;
            });
        }
        return $managers;
    }
}
