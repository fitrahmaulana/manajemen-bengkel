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
        $invoice = $payment->invoice;

        if ($invoice) {
            $invoice->refresh(); // Make sure totals are up-to-date

            if ($invoice->balance_due <= 0) {
                $invoice->status = 'paid';
                $invoice->save();
                Notification::make()
                    ->title('Invoice Paid')
                    ->body("Invoice {$invoice->invoice_number} has been fully paid.")
                    ->success()
                    ->sendToDatabase(auth()->user()); // Optional: send to specific users
            } elseif ($invoice->status !== 'overdue' && $invoice->status !== 'paid') {
                // If not fully paid, and not already overdue, set to 'sent' (or 'partially_paid')
                // Avoid changing 'paid' status here if for some reason balance_due became > 0 by other means
                $invoice->status = 'sent';
                $invoice->save();
            }
        }
    }
}
