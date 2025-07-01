<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Invoice;
use Filament\Notifications\Notification;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function ($record) {
                    // This 'after' hook for DeleteAction on the Edit page
                    // is triggered after the payment record itself is deleted.
                    $invoice = $record->invoice()->withTrashed()->first(); // Get invoice even if it's soft-deleted
                    if ($invoice) {
                        $invoice->refresh();
                         // If the invoice was 'paid' but now has a balance, update its status
                        if ($invoice->balance_due > 0 && $invoice->status === 'paid') {
                            $invoice->status = 'partially_paid'; // Or other appropriate status
                            $invoice->save();
                             Notification::make()
                                ->title('Invoice Status Updated')
                                ->body("Invoice {$invoice->invoice_number} status updated due to payment deletion.")
                                ->info()
                                ->sendToDatabase(auth()->user());
                        }
                    }
                }),
            Actions\ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        // This hook is called after the payment record is saved (updated).
        $payment = $this->record;
        $invoice = $payment->invoice;

        if ($invoice) {
            $invoice->refresh(); // Recalculate balance_due based on the updated payment

            if ($invoice->balance_due <= 0) {
                if ($invoice->status !== 'paid') {
                    $invoice->status = 'paid';
                    $invoice->save();
                    Notification::make()
                        ->title('Invoice Paid')
                        ->body("Invoice {$invoice->invoice_number} has been fully paid.")
                        ->success()
                        ->sendToDatabase(auth()->user());
                }
            } else {
                // If balance is now due, and status was 'paid', change it.
                // Also, if it's not 'overdue', set to 'partially_paid'.
                if ($invoice->status === 'paid' || ($invoice->status !== 'overdue' && $invoice->status !== 'partially_paid')) {
                    $invoice->status = 'partially_paid'; // Or 'partially_paid'
                    $invoice->save();
                     Notification::make()
                        ->title('Invoice Status Updated')
                        ->body("Invoice {$invoice->invoice_number} status updated due to payment modification.")
                        ->info()
                        ->sendToDatabase(auth()->user());
                }
            }
        }
    }
}
