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
                    $invoice = $record->invoice()->withTrashed()->first();
                    if ($invoice) {
                        $invoice->refresh();

                        // POS Style status update
                        if ($invoice->total_paid_amount >= $invoice->total_amount) {
                            $invoice->status = 'paid';
                        } else if ($invoice->payments()->exists()) {
                            $invoice->status = 'partially_paid';
                        } else {
                            $invoice->status = 'unpaid';
                        }
                        $invoice->save();
                    }
                }),
        ];
    }
}
