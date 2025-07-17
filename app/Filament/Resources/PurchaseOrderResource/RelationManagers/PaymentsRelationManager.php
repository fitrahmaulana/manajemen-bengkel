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
                \Filament\Tables\Actions\CreateAction::make(),
            ]);
    }

    protected function canCreate(): bool
    {
        $owner = $this->getOwnerRecord();

        if ($owner instanceof \App\Models\PurchaseOrder) {
            return $owner->balance_due > 0;
        }

        return false;
    }
}
