<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

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

    /**
     * Handle after payment actions (create, edit, delete)
     */
    private function handleAfterPaymentAction(RelationManager $livewire, ?Payment $payment = null): void
    {
        /** @var Invoice $invoice */
        $invoice = $livewire->getOwnerRecord();
        if ($invoice) {
            $invoice->refresh();

            // Update status berdasarkan total pembayaran
            if ($invoice->total_paid_amount >= $invoice->total_amount) {
                $invoice->status = 'paid';
                $invoice->save();

                if ($payment && $invoice->overpayment > 0) {
                    \Filament\Notifications\Notification::make()
                        ->title('✅ Invoice Lunas dengan Kembalian')
                        ->body("Invoice {$invoice->invoice_number} lunas. Kembalian: Rp. " . number_format($invoice->overpayment, 0, ',', '.'))
                        ->success()
                        ->send();
                } elseif ($payment) {
                    \Filament\Notifications\Notification::make()
                        ->title('✅ Invoice Lunas')
                        ->body("Invoice {$invoice->invoice_number} telah lunas.")
                        ->success()
                        ->send();
                }
            } else if ($invoice->payments()->exists()) {
                $invoice->status = 'partially_paid';
                $invoice->save();

                if ($payment) {
                    $remaining = $invoice->balance_due;
                    \Filament\Notifications\Notification::make()
                        ->title('💰 Pembayaran Sebagian Berhasil')
                        ->body('Sisa tagihan: Rp. ' . number_format($remaining, 0, ',', '.'))
                        ->info()
                        ->send();
                }
            } else {
                $invoice->status = 'unpaid';
                $invoice->save();
            }

            // Success notification untuk pembayaran yang berhasil dicatat
            if ($payment) {
                \Filament\Notifications\Notification::make()
                    ->title('✅ Pembayaran Berhasil Dicatat')
                    ->body('Jumlah: Rp. ' . number_format($payment->amount_paid, 0, ',', '.') . ' via ' . strtoupper($payment->payment_method))
                    ->success()
                    ->send();
            }

            $livewire->dispatch('refresh');
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('payment_date')
                    ->label('Tanggal Pembayaran')
                    ->default(now())
                    ->required(),

                // Tampilkan total tagihan yang harus dibayar
                Forms\Components\Placeholder::make('total_tagihan')
                    ->label('Total Tagihan')
                    ->content(function (RelationManager $livewire, ?Model $record) {
                        $invoice = $livewire->getOwnerRecord();
                        if ($record) {
                            // Edit mode: tampilkan total tagihan original
                            return '🧾 Rp. ' . number_format($invoice->total_amount, 0, ',', '.');
                        } else {
                            // Create mode: tampilkan sisa tagihan
                            return '🧾 Rp. ' . number_format($invoice->balance_due, 0, ',', '.');
                        }
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
                    ->afterStateUpdated(function ($state, $set, RelationManager $livewire, ?Model $record) {
                        $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)$state);
                        $invoice = $livewire->getOwnerRecord();

                        if ($record) {
                            // Edit mode: hitung berdasarkan total tagihan dikurangi pembayaran lain
                            $otherPayments = $invoice->payments()->where('id', '!=', $record->id)->sum('amount_paid');
                            $remainingBill = $invoice->total_amount - $otherPayments;

                            if ($amountPaid > $remainingBill) {
                                $overpayment = $amountPaid - $remainingBill;
                                $set('change_amount', $overpayment);
                                $set('payment_status', 'overpaid');
                            } elseif ($amountPaid == $remainingBill) {
                                $set('change_amount', 0);
                                $set('payment_status', 'exact');
                            } else {
                                $set('change_amount', 0);
                                $set('payment_status', 'underpaid');
                            }
                        } else {
                            // Create mode: hitung berdasarkan balance_due
                            $balanceDue = $invoice->balance_due;

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
                    ->default(function (RelationManager $livewire, ?Model $record) {
                        $invoice = $livewire->getOwnerRecord();
                        if ($record) {
                            // Edit mode: gunakan nilai pembayaran yang sudah ada
                            return $record->amount_paid;
                        } else {
                            // Create mode: gunakan balance_due
                            return $invoice->balance_due;
                        }
                    }),

                Forms\Components\ToggleButtons::make('quick_payment_options')
                    ->label('Pilihan Cepat Pembayaran')
                    ->helperText('Klik salah satu tombol untuk mengisi jumlah bayar secara otomatis.')
                    ->live()
                    ->afterStateUpdated(fn($state, $set) => $set('amount_paid', $state))
                    ->options(function (RelationManager $livewire, ?Model $record): array {
                        $invoice = $livewire->getOwnerRecord();

                        if ($record) {
                            // Edit mode: hitung berdasarkan total tagihan dikurangi pembayaran lain
                            $otherPayments = $invoice->payments()->where('id', '!=', $record->id)->sum('amount_paid');
                            $totalBill = $invoice->total_amount - $otherPayments;
                        } else {
                            // Create mode: gunakan balance_due
                            $totalBill = $invoice->balance_due;
                        }

                        if (!$totalBill || $totalBill <= 0) {
                            return [];
                        }

                        $options = [];
                        $suggestions = [];

                        // 1. Opsi Uang Pas
                        $options[(string)$totalBill] = '💰 Uang Pas';

                        // 2. Daftar pembulatan umum
                        $roundingBases = [10000, 20000, 50000, 100000];

                        foreach ($roundingBases as $base) {
                            if ($base >= $totalBill) {
                                $suggestions[] = ceil($totalBill / $base) * $base;
                            }
                        }

                        // Tambahkan juga pembulatan ke 100rb terdekat jika tagihan besar
                        if ($totalBill > 50000) {
                            $suggestions[] = ceil($totalBill / 100000) * 100000;
                        }

                        // 3. Saring hasil dan ambil 3 terbaik
                        $uniqueSuggestions = array_unique($suggestions);
                        sort($uniqueSuggestions);

                        $finalSuggestions = array_slice($uniqueSuggestions, 0, 3);

                        foreach ($finalSuggestions as $s) {
                            if ($s != $totalBill) {
                                $options[(string)$s] = '💵 Rp. ' . number_format($s, 0, ',', '.');
                            }
                        }

                        return $options;
                    })
                    ->columns(4)->visible(fn(string $operation) => $operation === 'create' ? true : false),

                // Kalkulator Kembalian - Real-time
                Forms\Components\Placeholder::make('kembalian_calculator')
                    ->label('Kembalian')
                    ->content(function ($get, RelationManager $livewire, ?Model $record) {
                        $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)($get('amount_paid') ?? '0'));
                        $invoice = $livewire->getOwnerRecord();

                        if ($record) {
                            // Edit mode: hitung berdasarkan total tagihan dikurangi pembayaran lain
                            $otherPayments = $invoice->payments()->where('id', '!=', $record->id)->sum('amount_paid');
                            $remainingBill = $invoice->total_amount - $otherPayments;
                            $change = $amountPaid - $remainingBill;
                        } else {
                            // Create mode: hitung berdasarkan balance_due
                            $balanceDue = $invoice->balance_due;
                            $change = $amountPaid - $balanceDue;
                        }

                        return '💵 Rp. ' . number_format($change, 0, ',', '.');
                    })
                    ->extraAttributes(function ($get, RelationManager $livewire, ?Model $record) {
                        $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)($get('amount_paid') ?? '0'));
                        $invoice = $livewire->getOwnerRecord();

                        if ($record) {
                            // Edit mode: hitung berdasarkan total tagihan dikurangi pembayaran lain
                            $otherPayments = $invoice->payments()->where('id', '!=', $record->id)->sum('amount_paid');
                            $remainingBill = $invoice->total_amount - $otherPayments;

                            if ($amountPaid >= $remainingBill && $amountPaid > 0) {
                                // Overpayment or exact payment - green
                                return ['class' => 'bg-green-50 border border-green-200 rounded-lg p-4 text-green-700 font-bold text-xl'];
                            } elseif ($amountPaid > 0 && $amountPaid < $remainingBill) {
                                // Underpayment - red
                                return ['class' => 'bg-red-50 border border-red-200 rounded-lg p-4 text-red-700 font-bold text-xl'];
                            }
                        } else {
                            // Create mode: hitung berdasarkan balance_due
                            $balanceDue = $invoice->balance_due;

                            if ($amountPaid >= $balanceDue && $amountPaid > 0) {
                                // Overpayment or exact payment - green
                                return ['class' => 'bg-green-50 border border-green-200 rounded-lg p-4 text-green-700 font-bold text-xl'];
                            } elseif ($amountPaid > 0 && $amountPaid < $balanceDue) {
                                // Underpayment - red
                                return ['class' => 'bg-red-50 border border-red-200 rounded-lg p-4 text-red-700 font-bold text-xl'];
                            }
                        }

                        // Waiting for input - gray
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment_date') // Or a more descriptive composite title
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->date('d M Y')
                    ->label('Tanggal Pembayaran')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Jumlah yang Dibayar')
                    ->currency('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->badge()
                    ->searchable(),
            ])
            ->filters([
                // Filters can be added if needed
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Catat Pembayaran')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Ensure amount_paid is correctly parsed from currency mask
                        $data['amount_paid'] = $this->parseCurrencyMask((string)$data['amount_paid']);
                        return $data;
                    })
                    ->before(function (array $data, RelationManager $livewire) {
                        $amountPaid = $this->parseCurrencyMask((string)$data['amount_paid']);
                        $balanceDue = $livewire->getOwnerRecord()->balance_due;

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
                                ->body('Jumlah pembayaran (Rp. ' . number_format($amountPaid, 0, ',', '.') . ') kurang dari total tagihan (Rp. ' . number_format($balanceDue, 0, ',', '.') . ')')
                                ->warning()
                                ->send();
                        }
                    })
                    ->after(function (RelationManager $livewire, Model $record) {
                        $this->handleAfterPaymentAction($livewire, $record);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['amount_paid'] = $this->parseCurrencyMask((string)$data['amount_paid']);
                        return $data;
                    })
                    ->before(function (array $data, RelationManager $livewire, ?Model $record) {
                        $amountPaid = $this->parseCurrencyMask((string)$data['amount_paid']);
                        $invoice = $livewire->getOwnerRecord();

                        // Validasi pembayaran minimum
                        if ($amountPaid <= 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('⚠️ Jumlah Pembayaran Tidak Valid')
                                ->body('Jumlah pembayaran harus lebih dari 0.')
                                ->warning()
                                ->send();
                            $this->halt();
                        }

                        if ($record) {
                            // Edit mode: hitung berdasarkan total tagihan dikurangi pembayaran lain
                            $otherPayments = $invoice->payments()->where('id', '!=', $record->id)->sum('amount_paid');
                            $remainingBill = $invoice->total_amount - $otherPayments;

                            // Peringatan jika pembayaran kurang untuk edit mode
                            if ($amountPaid < $remainingBill) {
                                \Filament\Notifications\Notification::make()
                                    ->title('⚠️ Pembayaran Kurang')
                                    ->body('Jumlah pembayaran (Rp. ' . number_format($amountPaid, 0, ',', '.') . ') kurang dari sisa tagihan (Rp. ' . number_format($remainingBill, 0, ',', '.') . ')')
                                    ->warning()
                                    ->send();
                            }
                        }
                    })
                    ->after(function (RelationManager $livewire, Model $record) {
                        $this->handleAfterPaymentAction($livewire, $record);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function (RelationManager $livewire, Payment $record) {
                        $this->handleAfterPaymentAction($livewire);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function (RelationManager $livewire, \Illuminate\Database\Eloquent\Collection $records) {
                            $this->handleAfterPaymentAction($livewire);
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
