<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Filament\Notifications\Notification;

class InvoiceStatusService
{
    /**
     * Update invoice status based on payments
     */
    public static function updateInvoiceStatus(Invoice $invoice): void
    {
        if ($invoice->total_paid_amount >= $invoice->total_amount) {
            $invoice->status = 'paid';
        } else if ($invoice->payments()->exists()) {
            $invoice->status = 'partially_paid';
        } else {
            $invoice->status = 'unpaid';
        }
        $invoice->save();
    }

    /**
     * Handle invoice status after payment creation/update
     */
    public static function handlePaymentComplete(?Payment $payment): void
    {
        if (!$payment || !$payment->invoice) {
            return;
        }

        $invoice = $payment->invoice;
        $invoice->refresh();
        self::updateInvoiceStatus($invoice);

        if ($invoice->total_paid_amount >= $invoice->total_amount) {
            if ($invoice->overpayment > 0) {
                Notification::make()
                    ->title('✅ Invoice Lunas dengan Kembalian')
                    ->body("Invoice {$invoice->invoice_number} lunas. Kembalian: " . PaymentCalculationService::formatCurrency($invoice->overpayment))
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
            Notification::make()
                ->title('💰 Pembayaran Diperbarui')
                ->body('Sisa tagihan: ' . PaymentCalculationService::formatCurrency($invoice->balance_due))
                ->info()
                ->send();
        }

        Notification::make()
            ->title('✅ Pembayaran Berhasil Diperbarui')
            ->body('Jumlah: ' . PaymentCalculationService::formatCurrency($payment->amount_paid) . ' via ' . strtoupper($payment->payment_method))
            ->success()
            ->send();
    }

    /**
     * Handle invoice status after payment deletion
     */
    public static function handlePaymentDeletion(?Payment $payment): void
    {
        if (!$payment || !$payment->invoice) {
            return;
        }

        $invoice = $payment->invoice;
        $invoice->refresh();
        self::updateInvoiceStatus($invoice);
    }

    /**
     * Handle invoice status after bulk payment deletion
     */
    public static function handleBulkPaymentDeletion(\Illuminate\Database\Eloquent\Collection $payments): void
    {
        $payments->each(function (Payment $payment) {
            self::handlePaymentDeletion($payment);
        });
    }
}
