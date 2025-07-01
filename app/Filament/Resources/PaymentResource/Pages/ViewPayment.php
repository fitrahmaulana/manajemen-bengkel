<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
             ->after(function ($record) {
                    // This 'after' hook for DeleteAction on the View page
                    $invoice = $record->invoice()->withTrashed()->first();
                    if ($invoice) {
                        $invoice->refresh();
                        if ($invoice->balance_due > 0 && $invoice->status === 'paid') {
                            $invoice->status = 'partially_paid';
                            $invoice->save();
                            // Optionally send notification
                        }
                    }
                }),
        ];
    }
}
