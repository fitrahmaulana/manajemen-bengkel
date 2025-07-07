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


    public function form(Form $form): Form
    {
        return PaymentResource::form($form);
    }

    public function table(Table $table): Table
    {
        return PaymentResource::table($table)
            ->recordTitleAttribute('payment_date')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Otomatis set invoice_id dari parent record
                        $data['invoice_id'] = $this->getOwnerRecord()->id;

                        // Pastikan payment_date ada jika tidak diisi
                        if (!isset($data['payment_date']) || empty($data['payment_date'])) {
                            $data['payment_date'] = now()->toDateString();
                        }

                        return $data;
                    })
                    ->after(function ($record, RelationManager $livewire) {
                        // Gunakan method yang sudah ada di PaymentResource
                        PaymentResource::handleAfterPaymentAction($record);
                        $livewire->dispatch('refresh');
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->after(function ($record, RelationManager $livewire) {
                        // Gunakan method yang sudah ada di PaymentResource
                        PaymentResource::handleAfterPaymentAction($record);
                        $livewire->dispatch('refresh');
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($record, RelationManager $livewire) {
                        // Gunakan method yang sudah ada di PaymentResource
                        PaymentResource::handleAfterPaymentAction($record);
                        $livewire->dispatch('refresh');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function ($records, RelationManager $livewire) {
                            // Update status invoice untuk setiap record yang dihapus
                            foreach ($records as $record) {
                                PaymentResource::handleAfterPaymentAction($record);
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
