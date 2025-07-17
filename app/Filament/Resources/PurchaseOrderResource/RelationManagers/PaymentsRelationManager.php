<?php

namespace App\Filament\Resources\PurchaseOrderResource\RelationManagers;

use App\Filament\Resources\PaymentResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Form $form): Form
    {
        return PaymentResource::form($form);
    }

    public function table(Table $table): Table
    {
        return PaymentResource::table($table)
            ->headerActions([
                \Filament\Tables\Actions\CreateAction::make()
                    ->after(function (\Filament\Resources\RelationManagers\RelationManager $livewire) {
                        $livewire->dispatch('refresh');
                    }),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make()
                    ->after(function (\Filament\Resources\RelationManagers\RelationManager $livewire) {
                        $livewire->dispatch('refresh');
                    }),
                \Filament\Tables\Actions\DeleteAction::make()
                    ->after(function (\Filament\Resources\RelationManagers\RelationManager $livewire) {
                        $livewire->dispatch('refresh');
                    }),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    protected function canCreate(): bool
    {
        $owner = $this->getOwnerRecord();

        if ($owner instanceof \App\Models\PurchaseOrder) {
            $totalPaid = $owner->payments()->sum('amount_paid');
            return $owner->total_amount > $totalPaid;
        }

        return false;
    }
}
