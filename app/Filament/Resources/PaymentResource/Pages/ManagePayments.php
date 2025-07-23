<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\Payment;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePayments extends ManageRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    // Parse currency mask to float value
                    $data['amount_paid'] = (float) str_replace(['Rp. ', '.'], ['', ''], (string) $data['amount_paid']);

                    return $data;
                })
                ->before(function (array $data) {
                    $amountPaid = (float) str_replace(['Rp. ', '.'], ['', ''], (string) $data['amount_paid']);
                    $invoice = \App\Models\Invoice::find($data['invoice_id']);

                    if ($invoice) {
                        $balanceDue = $invoice->balance_due;

                        // Validasi pembayaran minimum
                        if ($amountPaid <= 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('⚠️ Jumlah Pembayaran Tidak Valid')
                                ->body('Jumlah pembayaran harus lebih dari 0.')
                                ->warning()
                                ->send();
                            $this->halt();
                        }

                        // Peringatan jika pembayaran kurang
                        if ($amountPaid < $balanceDue) {
                            \Filament\Notifications\Notification::make()
                                ->title('⚠️ Pembayaran Kurang')
                                ->body('Jumlah pembayaran (Rp. '.number_format($amountPaid, 0, ',', '.').') kurang dari total tagihan (Rp. '.number_format($balanceDue, 0, ',', '.').')')
                                ->warning()
                                ->send();
                        }
                    }
                })
                ->after(function (Payment $record) {
                    $invoice = $record->invoice;
                    if ($invoice) {
                        $invoice->refresh();

                        // Update status berdasarkan total pembayaran
                        if ($invoice->total_paid_amount >= $invoice->total_amount) {
                            $invoice->status = 'paid';
                            $invoice->save();

                            if ($invoice->overpayment > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('✅ Invoice Lunas dengan Kembalian')
                                    ->body("Invoice {$invoice->invoice_number} lunas. Kembalian: Rp. ".number_format($invoice->overpayment, 0, ',', '.'))
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('✅ Invoice Lunas')
                                    ->body("Invoice {$invoice->invoice_number} telah lunas.")
                                    ->success()
                                    ->send();
                            }
                        } elseif ($invoice->payments()->exists()) {
                            $invoice->status = 'partially_paid';
                            $invoice->save();
                            $remaining = $invoice->balance_due;

                            \Filament\Notifications\Notification::make()
                                ->title('💰 Pembayaran Sebagian Berhasil')
                                ->body('Sisa tagihan: Rp. '.number_format($remaining, 0, ',', '.'))
                                ->info()
                                ->send();
                        } else {
                            $invoice->status = 'unpaid';
                            $invoice->save();
                        }

                        // Success notification untuk pembayaran yang berhasil dicatat
                        \Filament\Notifications\Notification::make()
                            ->title('✅ Pembayaran Berhasil Dicatat')
                            ->body('Jumlah: Rp. '.number_format($record->amount_paid, 0, ',', '.').' via '.strtoupper($record->payment_method))
                            ->success()
                            ->send();
                    }
                }),

        ];
    }
}
