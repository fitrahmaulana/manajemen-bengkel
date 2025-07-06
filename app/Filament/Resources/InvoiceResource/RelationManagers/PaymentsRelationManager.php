<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use App\Filament\Resources\PaymentResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use App\Models\Invoice;
use App\Models\Payment;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';
    protected static ?string $recordTitleAttribute = 'payment_date';
    protected static ?string $title = 'Riwayat Pembayaran';

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    /**
     * Parse currency mask to float value
     */
    private function parseCurrencyMask(string $value): float
    {
        return (float)str_replace(['Rp. ', '.'], ['', ''], $value);
    }

    public function form(Form $form): Form
    {
        return PaymentResource::form($form);
    }

    public function table(Table $table): Table
    {
        return PaymentResource::table($table)
            ->recordTitleAttribute('payment_date')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function (RelationManager $livewire) {
                        // Since we're in relation manager, we can directly access the owner invoice
                        $invoice = $livewire->getOwnerRecord();
                        if ($invoice) {
                            $invoice->refresh();

                            // Update status berdasarkan total pembayaran
                            if ($invoice->total_paid_amount >= $invoice->total_amount) {
                                $invoice->status = 'paid';
                            } else if ($invoice->payments()->exists()) {
                                $invoice->status = 'partially_paid';
                            } else {
                                $invoice->status = 'unpaid';
                            }
                            $invoice->save();
                        }

                        $livewire->dispatch('refresh');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function (RelationManager $livewire) {
                            // Since we're in relation manager, we can directly access the owner invoice
                            $invoice = $livewire->getOwnerRecord();
                            if ($invoice) {
                                $invoice->refresh();

                                // Update status berdasarkan total pembayaran
                                if ($invoice->total_paid_amount >= $invoice->total_amount) {
                                    $invoice->status = 'paid';
                                } else if ($invoice->payments()->exists()) {
                                    $invoice->status = 'partially_paid';
                                } else {
                                    $invoice->status = 'unpaid';
                                }
                                $invoice->save();
                            }

                            $livewire->dispatch('refresh');
                        }),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    protected function canCreate(): bool
    {
        /** @var Invoice $invoice */
        $invoice = $this->getOwnerRecord();
        if (!$invoice) {
            return false;
        }
        return $invoice->balance_due > 0;
    }
}
