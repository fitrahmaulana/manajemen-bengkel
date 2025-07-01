<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Invoice;
use Filament\Notifications\Notification;


class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
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
                // If balance is due, and it's not 'overdue', set to 'sent'.
                // (If it was 'draft', it should become 'sent' after first payment)
                if ($currentStatus !== 'overdue') {
                    $newStatus = 'sent';
                }
            }

            if ($newStatus !== $currentStatus) {
                $invoice->status = $newStatus;
                $invoice->save();
                Notification::make()
                    ->title('Invoice Status Updated')
                    ->body("Invoice {$invoice->invoice_number} status automatically updated to {$invoice->status}.")
                    ->success()
                    ->sendToDatabase(auth()->user()); // Optional
            } elseif ($newStatus === 'paid' && $newStatus === $currentStatus) {
                // If it was already paid and still paid (e.g. editing payment but still fully covered)
                // or if a new payment made it paid.
                 Notification::make()
                    ->title('Invoice Paid')
                    ->body("Invoice {$invoice->invoice_number} is fully paid.")
                    ->success()
                    ->sendToDatabase(auth()->user());
            }
        }
    }
}
