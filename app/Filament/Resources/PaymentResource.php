<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Traits\InvoiceCalculationTrait;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    use InvoiceCalculationTrait;

    protected static ?string $model = Payment::class;
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Pembayaran';
    protected static ?string $modelLabel = 'Pembayaran';
    protected static ?string $pluralModelLabel = 'Daftar Pembayaran';
    protected static ?int $navigationSort = 2;

    private static function getPayableFromContext(Forms\Get $get, $record, $livewire): ?Model
    {
        if ($record && $record->payable) {
            return $record->payable;
        }

        if ($livewire instanceof \App\Filament\Resources\InvoiceResource\RelationManagers\PaymentsRelationManager || $livewire instanceof \App\Filament\Resources\PurchaseOrderResource\RelationManagers\PaymentsRelationManager) {
            $owner = $livewire->getOwnerRecord();
            if ($owner instanceof Invoice || $owner instanceof PurchaseOrder) {
                return $owner;
            }
        }

        $payableType = $get('payable_type');
        $payableId = $get('payable_id');

        if ($payableType && $payableId) {
            return $payableType::find($payableId);
        }

        return null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\MorphToSelect::make('payable')
                            ->label('Tagihan')
                            ->types([
                                Forms\Components\MorphToSelect\Type::make(Invoice::class)
                                    ->titleAttribute('invoice_number'),
                                Forms\Components\MorphToSelect\Type::make(PurchaseOrder::class)
                                    ->titleAttribute('po_number'),
                            ])
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull()
                            ->live(),

                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Tanggal Pembayaran')
                            ->default(now())
                            ->required(),

                        Forms\Components\Placeholder::make('total_tagihan')
                            ->label('Total Tagihan')
                            ->content(function (Forms\Get $get, $record, $livewire) {
                                $payable = self::getPayableFromContext($get, $record, $livewire);
                                if ($payable) {
                                    if ($record) {
                                        return '🧾 ' . self::formatCurrency($payable->total_amount);
                                    }
                                    return '🧾 ' . self::formatCurrency($payable->balance_due ?? $payable->total_amount);
                                }
                                return '🧾 Pilih tagihan terlebih dahulu';
                            })
                            ->extraAttributes([
                                'class' => 'border border-200 rounded-lg p-3 font-bold',
                                'style' => 'margin: 8px 0;'
                            ]),

                        Forms\Components\TextInput::make('amount_paid')
                            ->label('Jumlah Uang Diterima')
                            ->numeric()
                            ->prefix('Rp.')
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                            ->required()
                            ->minValue(1)
                            ->helperText('Masukkan jumlah uang tunai yang diterima dari pelanggan')
                            ->live(debounce: 300)
                            ->afterStateUpdated(function ($state, $set, $get, $record, $livewire) {
                                $payable = self::getPayableFromContext($get, $record, $livewire);
                                if ($payable) {
                                    $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)$state);
                                    $balanceDue = $payable->balance_due ?? $payable->total_amount;

                                    if ($amountPaid > $balanceDue) {
                                        $overpayment = $amountPaid - $balanceDue;
                                        $set('change_amount', $overpayment);
                                        $set('payment_status', 'overpaid');
                                    } elseif ($amountPaid == $balanceDue) {
                                        $set('change_amount', 0);
                                        $set('payment_status', 'exact');
                                    } else {
                                        $set('change_amount', 0);
                                        $set('payment_status', 'underpaid');
                                    }
                                }
                            })
                            ->default(function ($get, $record, $livewire) {
                                if ($record) {
                                    return $record->amount_paid;
                                } else {
                                    $payable = self::getPayableFromContext($get, $record, $livewire);
                                    if ($payable) {
                                        return $payable->balance_due ?? $payable->total_amount;
                                    }
                                    return null;
                                }
                            }),

                        Forms\Components\Placeholder::make('kembalian_calculator')
                            ->label('Kembalian')
                            ->content(function (Forms\Get $get, $record, $livewire) {
                                $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)($get('amount_paid') ?? '0'));
                                $payable = self::getPayableFromContext($get, $record, $livewire);

                                if (!$payable) {
                                    return '💵 Rp. 0';
                                }

                                $balanceDue = $payable->balance_due ?? $payable->total_amount;
                                $change = $amountPaid - $balanceDue;

                                return '💵 ' . self::formatCurrency($change);
                            })
                            ->extraAttributes(function (Forms\Get $get, $record, $livewire) { // Explicitly type Forms\Get
                                $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)($get('amount_paid') ?? '0'));
                                $payable = self::getPayableFromContext($get, $record, $livewire);

                                if (!$payable) {
                                    return ['class' => 'bg-gray-50 border border-gray-200 rounded-lg p-4 text-gray-700 font-bold text-xl'];
                                }

                                $targetAmount = $payable->balance_due ?? $payable->total_amount;

                                if ($amountPaid >= $targetAmount && $amountPaid > 0) {
                                    return ['class' => 'bg-green-50 border border-green-200 rounded-lg p-4 text-green-700 font-bold text-xl'];
                                } elseif ($amountPaid > 0 && $amountPaid < $targetAmount) {
                                    return ['class' => 'bg-red-50 border border-red-200 rounded-lg p-4 text-red-700 font-bold text-xl'];
                                }
                                return ['class' => 'bg-gray-50 border border-gray-200 rounded-lg p-4 text-gray-700 font-bold text-xl'];
                            }),

                        Forms\Components\Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'cash' => 'Cash',
                                'transfer' => 'Bank Transfer',
                                'qris' => 'QRIS',
                            ])
                            ->default('cash')
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan (Optional)')
                            ->rows(3),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payable.invoice_number')
                    ->label('Nomor Tagihan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->date('d M Y')
                    ->label('Tanggal Pembayaran')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Jumlah Dibayar')
                    ->currency('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Payment $record) {
                        $record->loadMissing('payable');
                    })
                    ->after(function (Payment $record) {
                        self::handleAfterPaymentAction($record);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->load('payable');
                        })
                        ->after(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $payables = $records->pluck('payable')->unique('id');

                            foreach ($payables as $payable) {
                                if ($payable instanceof Invoice) {
                                    self::updateInvoiceStatus($payable);
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePayments::route('/'),
        ];
    }

    public static function handleAfterPaymentAction(?Payment $payment = null): void
    {
        if (!$payment) {
            return;
        }

        $payable = $payment->payable;

        if ($payable instanceof Invoice) {
            self::updateInvoiceStatus($payable);
            $payable->refresh();

            $newStatus = $payable->status;
            $balanceDue = $payable->balance_due;
            $overpayment = $payable->overpayment;

            if ($newStatus === 'paid') {
                if ($overpayment > 0) {
                    \Filament\Notifications\Notification::make()
                        ->title('✅ Invoice Lunas dengan Kembalian')
                        ->body("Invoice {$payable->invoice_number} lunas. Kembalian: Rp. " . number_format($overpayment, 0, ',', '.'))
                        ->success()
                        ->send();
                } else {
                    \Filament\Notifications\Notification::make()
                        ->title('✅ Invoice Lunas')
                        ->body("Invoice {$payable->invoice_number} telah lunas.")
                        ->success()
                        ->send();
                }
            } elseif ($newStatus === 'partially_paid') {
                \Filament\Notifications\Notification::make()
                    ->title('💰 Status Pembayaran Diperbarui')
                    ->body("Invoice {$payable->invoice_number} sebagian dibayar. Sisa tagihan: Rp. " . number_format($balanceDue, 0, ',', '.'))
                    ->info()
                    ->send();
            } elseif ($newStatus === 'unpaid') {
                \Filament\Notifications\Notification::make()
                    ->title('📋 Status Invoice Diperbarui')
                    ->body("Invoice {$payable->invoice_number} menjadi belum dibayar. Sisa tagihan: Rp. " . number_format($balanceDue, 0, ',', '.'))
                    ->warning()
                    ->send();
            } elseif ($newStatus === 'overdue') {
                 \Filament\Notifications\Notification::make()
                    ->title('⚠️ Invoice Jatuh Tempo')
                    ->body("Invoice {$payable->invoice_number} telah jatuh tempo. Sisa tagihan: Rp. " . number_format($balanceDue, 0, ',', '.'))
                    ->danger()
                    ->send();
            }
        }
    }
}
