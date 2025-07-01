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
                            $invoice->status = 'sent'; // Or other appropriate status
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
        /** @var Invoice $invoice */
        $invoice = $payment->invoice;

        if ($invoice) {
            $invoice->refresh();

            $currentStatus = $invoice->status;
            $newStatus = $currentStatus;

            if ($invoice->balance_due <= 0) {
                $newStatus = 'paid';
            } else {
                // Balance is due
                if ($currentStatus === 'paid') { // Was paid, but now isn't due to this edit
                    $newStatus = 'sent';
                } elseif ($currentStatus !== 'overdue') { // Don't override overdue unless it becomes paid by this edit
                    $newStatus = 'sent';
                }
                // If $currentStatus is 'overdue' and balance_due > 0, it remains 'overdue'.
            }

            if ($newStatus !== $currentStatus) {
                $invoice->status = $newStatus;
                $invoice->save();
                Notification::make()
                    ->title('Invoice Status Updated')
                    ->body("Invoice {$invoice->invoice_number} status automatically updated to {$invoice->status}.")
                    ->success()
                    ->sendToDatabase(auth()->user());
            } elseif ($newStatus === 'paid' && $newStatus === $currentStatus && $this->record->wasChanged('amount_paid')) {
                 // If it was already paid and still paid, but the payment amount changed.
                 Notification::make()
                    ->title('Invoice Still Paid')
                    ->body("Invoice {$invoice->invoice_number} remains fully paid after payment update.")
                    ->success()
                    ->sendToDatabase(auth()->user());
            }
        }
    }
}
