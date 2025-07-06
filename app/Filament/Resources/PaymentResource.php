<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Pembayaran';
    protected static ?string $modelLabel = 'Pembayaran';
    protected static ?string $pluralModelLabel = 'Daftar Pembayaran';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('invoice_id')
                    ->relationship('invoice', 'invoice_number')
                    ->label('Invoice Number')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull()
                    ->live(),

                Forms\Components\DatePicker::make('payment_date')
                    ->label('Tanggal Pembayaran')
                    ->default(now())
                    ->required(),

                // Tampilkan total tagihan yang harus dibayar
                Forms\Components\Placeholder::make('total_tagihan')
                    ->label('Total Tagihan')
                    ->content(function ($get) {
                        $invoiceId = $get('invoice_id');
                        if ($invoiceId) {
                            $invoice = \App\Models\Invoice::find($invoiceId);
                            if ($invoice) {
                                return '🧾 Rp. ' . number_format($invoice->balance_due, 0, ',', '.');
                            }
                        }
                        return '🧾 Pilih invoice terlebih dahulu';
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
                    ->afterStateUpdated(function ($state, $set, $get) {
                        $invoiceId = $get('invoice_id');
                        if ($invoiceId) {
                            $invoice = \App\Models\Invoice::find($invoiceId);
                            if ($invoice) {
                                $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)$state);
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
                        }
                    })
                    ->default(function ($get) {
                        $invoiceId = $get('invoice_id');
                        if ($invoiceId) {
                            $invoice = \App\Models\Invoice::find($invoiceId);
                            if ($invoice) {
                                return $invoice->balance_due;
                            }
                        }
                        return null;
                    }),

                Forms\Components\ToggleButtons::make('quick_payment_options')
                    ->label('Pilihan Cepat Pembayaran')
                    ->helperText('Klik salah satu tombol untuk mengisi jumlah bayar secara otomatis.')
                    ->live()
                    ->afterStateUpdated(fn($state, $set) => $set('amount_paid', $state))
                    ->options(function ($get): array {
                        $invoiceId = $get('invoice_id');
                        if (!$invoiceId) {
                            return [];
                        }

                        $invoice = \App\Models\Invoice::find($invoiceId);
                        if (!$invoice) {
                            return [];
                        }

                        $totalBill = $invoice->balance_due;
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
                    ->columns(4),

                // Kalkulator Kembalian - Real-time
                Forms\Components\Placeholder::make('kembalian_calculator')
                    ->label('Kembalian')
                    ->content(function ($get) {
                        $invoiceId = $get('invoice_id');
                        $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)($get('amount_paid') ?? '0'));

                        if ($invoiceId) {
                            $invoice = \App\Models\Invoice::find($invoiceId);
                            if ($invoice) {
                                $balanceDue = $invoice->balance_due;
                                $change = $amountPaid - $balanceDue;
                                return '💵 Rp. ' . number_format($change, 0, ',', '.');
                            }
                        }
                        return '💵 Rp. 0';
                    })
                    ->extraAttributes(function ($get) {
                        $invoiceId = $get('invoice_id');
                        $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)($get('amount_paid') ?? '0'));

                        if ($invoiceId) {
                            $invoice = \App\Models\Invoice::find($invoiceId);
                            if ($invoice) {
                                $balanceDue = $invoice->balance_due;

                                if ($amountPaid >= $balanceDue && $amountPaid > 0) {
                                    // Overpayment or exact payment - green
                                    return ['class' => 'bg-green-50 border border-green-200 rounded-lg p-4 text-green-700 font-bold text-xl'];
                                } elseif ($amountPaid > 0 && $amountPaid < $balanceDue) {
                                    // Underpayment - red
                                    return ['class' => 'bg-red-50 border border-red-200 rounded-lg p-4 text-red-700 font-bold text-xl'];
                                }
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice Number')
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
                Tables\Actions\EditAction::make()->form([
                    Forms\Components\Select::make('invoice_id')
                        ->relationship('invoice', 'invoice_number')
                        ->label('Invoice Number')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull()
                        ->disabled(), // Disable editing invoice_id

                    Forms\Components\DatePicker::make('payment_date')
                        ->label('Tanggal Pembayaran')
                        ->required(),

                    // Tampilkan total tagihan untuk edit mode
                    Forms\Components\Placeholder::make('total_tagihan')
                        ->label('Total Tagihan')
                        ->content(function ($record) {
                            if ($record) {
                                $invoice = $record->invoice;
                                return '🧾 Rp. ' . number_format($invoice->total_amount, 0, ',', '.');
                            }
                            return '🧾 N/A';
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
                        ->afterStateUpdated(function ($state, $set, $record) {
                            if ($record) {
                                $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)$state);
                                $invoice = $record->invoice;

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
                            }
                        }),

                    // Kalkulator Kembalian untuk edit mode
                    Forms\Components\Placeholder::make('kembalian_calculator')
                        ->label('Kembalian')
                        ->content(function ($get, $record) {
                            if ($record) {
                                $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)($get('amount_paid') ?? '0'));
                                $invoice = $record->invoice;

                                // Edit mode: hitung berdasarkan total tagihan dikurangi pembayaran lain
                                $otherPayments = $invoice->payments()->where('id', '!=', $record->id)->sum('amount_paid');
                                $remainingBill = $invoice->total_amount - $otherPayments;
                                $change = $amountPaid - $remainingBill;

                                return '💵 Rp. ' . number_format($change, 0, ',', '.');
                            }
                            return '💵 Rp. 0';
                        })
                        ->extraAttributes(function ($get, $record) {
                            if ($record) {
                                $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)($get('amount_paid') ?? '0'));
                                $invoice = $record->invoice;

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
                        ->required(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan (Optional)')
                        ->rows(3),
                ])
                    ->mutateFormDataUsing(function (array $data): array {
                        // Parse currency mask to float value
                        $data['amount_paid'] = (float)str_replace(['Rp. ', '.'], ['', ''], (string)$data['amount_paid']);
                        return $data;
                    })
                    ->before(function (array $data, $record) {
                        $amountPaid = (float)str_replace(['Rp. ', '.'], ['', ''], (string)$data['amount_paid']);

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
                            $invoice = $record->invoice;
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
                                        ->body("Invoice {$invoice->invoice_number} lunas. Kembalian: Rp. " . number_format($invoice->overpayment, 0, ',', '.'))
                                        ->success()
                                        ->send();
                                } else {
                                    \Filament\Notifications\Notification::make()
                                        ->title('✅ Invoice Lunas')
                                        ->body("Invoice {$invoice->invoice_number} telah lunas.")
                                        ->success()
                                        ->send();
                                }
                            } else if ($invoice->payments()->exists()) {
                                $invoice->status = 'partially_paid';
                                $invoice->save();
                                $remaining = $invoice->balance_due;

                                \Filament\Notifications\Notification::make()
                                    ->title('💰 Pembayaran Diperbarui')
                                    ->body('Sisa tagihan: Rp. ' . number_format($remaining, 0, ',', '.'))
                                    ->info()
                                    ->send();
                            } else {
                                $invoice->status = 'unpaid';
                                $invoice->save();
                            }

                            // Success notification untuk pembayaran yang berhasil diperbarui
                            \Filament\Notifications\Notification::make()
                                ->title('✅ Pembayaran Berhasil Diperbarui')
                                ->body('Jumlah: Rp. ' . number_format($record->amount_paid, 0, ',', '.') . ' via ' . strtoupper($record->payment_method))
                                ->success()
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function (Payment $record) {
                        $invoice = $record->invoice;
                        if ($invoice) {
                            // Update invoice status after payment deletion
                            $invoice->refresh();
                            if ($invoice->total_paid_amount >= $invoice->total_amount) {
                                $invoice->status = 'paid';
                            } else if ($invoice->payments()->exists()) {
                                $invoice->status = 'partially_paid';
                            } else {
                                $invoice->status = 'unpaid';
                            }
                            $invoice->save();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(function (Payment $record) {
                                $invoice = $record->invoice;
                                if ($invoice) {
                                    // Update invoice status after payment deletion
                                    $invoice->refresh();
                                    if ($invoice->total_paid_amount >= $invoice->total_amount) {
                                        $invoice->status = 'paid';
                                    } else if ($invoice->payments()->exists()) {
                                        $invoice->status = 'partially_paid';
                                    } else {
                                        $invoice->status = 'unpaid';
                                    }
                                    $invoice->save();
                                }
                            });
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
}
