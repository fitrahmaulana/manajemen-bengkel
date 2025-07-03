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

            if ($invoice->total_paid_amount >= $invoice->total_amount) {
                // POS Style: Any payment >= total = Lunas
                $invoice->status = 'paid';
                $invoice->save();
                
                if ($invoice->overpayment > 0) {
                    Notification::make()
                        ->title('✅ Invoice Lunas')
                        ->body("Invoice {$invoice->invoice_number} lunas. Kembalian: Rp. " . number_format($invoice->overpayment, 0, ',', '.'))
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('✅ Invoice Lunas')
                        ->body("Invoice {$invoice->invoice_number} telah lunas.")
                        ->success()
                        ->send();
                }
            } else if ($invoice->payments()->exists()) {
                $invoice->status = 'partially_paid';
                $invoice->save();
                Notification::make()
                    ->title('💰 Pembayaran Sebagian')
                    ->body("Invoice {$invoice->invoice_number} sebagian dibayar. Sisa: Rp. " . number_format($invoice->balance_due, 0, ',', '.'))
                    ->info()
                    ->send();
            } else {
                $invoice->status = 'unpaid';
                $invoice->save();
            }
        }
    }
}
