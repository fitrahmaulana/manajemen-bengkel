<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Forms\Components\CustomTableRepeater;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Service;
use App\Models\Vehicle;
use App\Traits\InvoiceCalculationTrait;
use Awcodes\TableRepeater\Header;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Support\HtmlString;
use App\Services\ItemUnitConversionService; // Added import

class InvoiceResource extends Resource
{
    use InvoiceCalculationTrait; // Use the optimized calculation trait

    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Faktur';
    protected static ?string $modelLabel = 'Faktur';
    protected static ?string $pluralModelLabel = 'Daftar Faktur';
    protected static ?int $navigationSort = 1;

    // Removed private static function calculateToQuantityForInvoice(...)

    public static function form(Form $form): Form
    {
        // Optimized price update functions
        $updateServicePrice = function (Set $set, Get $get, $state) {
            if ($state) {
                $service = Service::find($state);
                if ($service) {
                    $set('price', $service->price);
                }
            }
        };

        $updateItemData = function (Set $set, Get $get, $state) {
            if ($state) {
                $item = Item::find($state);
                if ($item) {
                    $set('price', $item->selling_price);
                    $set('unit_name', $item->unit);
                }
            }
        };


        return $form->schema([
            // Bagian atas untuk detail invoice dan pelanggan
            Section::make()->schema([
                Grid::make(3)->schema([
                    Group::make()->schema([
                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->label('Pelanggan')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                // Reset vehicle_id when customer changes
                                $set('vehicle_id', null);
                            }),
                        Forms\Components\Select::make('vehicle_id')->label('Kendaraan (No. Polisi)')->options(fn(Get $get) => Vehicle::query()->where('customer_id', $get('customer_id'))->pluck('license_plate', 'id'))->searchable()->preload()->required(),
                    ]),
                    Group::make()->schema([
                        Forms\Components\TextInput::make('invoice_number')->label('Nomor Invoice')->default('INV-' . date('Ymd-His'))->required(),
                        Forms\Components\Select::make('status')->options(['unpaid' => 'Belum Dibayar', 'partially_paid' => 'Sebagian Dibayar', 'paid' => 'Lunas', 'overdue' => 'Jatuh Tempo',])->default('unpaid')->required(),
                    ]),
                    Group::make()->schema([
                        Forms\Components\DatePicker::make('invoice_date')->label('Tanggal Invoice')->default(now())->required(),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Tanggal Jatuh Tempo')
                            ->default(now()->addDays(7))
                            ->required()
                    ]),
                ]),
            ]),

            // Bagian Repeater yang sekarang dibuat lebih lebar
            Section::make('Detail Pekerjaan & Barang')->schema([
                // REPEATER JASA
                // Menggunakan relationship() untuk secara otomatis menghubungkan repeater dengan relasi 'invoiceServices' pada model Invoice.
                // Filament akan menangani pembuatan, pembaruan, dan penghapusan record pada tabel 'invoice_service'.
                CustomTableRepeater::make('invoiceServices')
                    ->relationship()
                    ->reorderAtStart()
                    ->hiddenLabel()
                    ->footerItem(
                        fn(Get $get) => new HtmlString(
                            'Total: ' . self::formatCurrency(collect($get('invoiceServices'))->sum(function ($service) {
                                return self::parseCurrencyValue($service['price'] ?? '0');
                            }))
                        )
                    )
                    ->headers([
                        Header::make('Nama Jasa')->width('50%'),
                        Header::make('Harga Jasa')->width('30%'),
                        Header::make('Subtotal')->width('20%')->align('center'),
                    ])
                    ->label('Jasa / Layanan')
                    ->schema([
                        Forms\Components\Group::make()->schema([
                            // SELECT SERVICE
                            // Menggunakan relationship() untuk menghubungkan Select ke relasi 'service' pada model InvoiceService.
                            // Ini memungkinkan pencarian dan pengambilan data service secara efisien.
                            Forms\Components\Select::make('service_id')
                                ->hiddenLabel()
                                ->relationship('service', 'name')
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated($updateServicePrice),
                            Forms\Components\Textarea::make('description')
                                ->hiddenLabel()
                                ->placeholder('Masukkan deskripsi pekerjaan')
                                ->rows(1),
                        ]),
                        Forms\Components\TextInput::make('price')
                            ->label('Harga Jasa')
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                            ->prefix('Rp. ')
                            ->live()
                            ->required(),
                        Forms\Components\Placeholder::make('subtotal_service')
                            ->hiddenLabel()
                            ->dehydrated(false)
                            ->extraAttributes(['class' => 'text-left md:text-center'])
                            ->content(function (Get $get) {
                                $price = self::parseCurrencyValue($get('price') ?? '0');
                                return self::formatCurrency($price);
                            }),
                    ])
                    ->columns(3),

                // REPEATER BARANG
                // Sama seperti jasa, repeater ini terhubung dengan relasi 'invoiceItems' pada model Invoice.
                CustomTableRepeater::make('invoiceItems')
                    ->relationship()
                    ->headers([
                        Header::make('Barang / Suku Cadang')->width('45%'),
                        Header::make('Kuantitas')->width('15%'),
                        Header::make('Harga Satuan')->width('20%'),
                        Header::make('Total')->width('20%')->align('center'),
                    ])
                    ->hiddenLabel()
                    ->reorderAtStart()
                    ->cloneable()
                    ->schema([
                        Forms\Components\Group::make()->schema([
                            // SELECT ITEM
                            // Menggunakan getOptionLabelFromRecordUsing untuk format label yang custom (menampilkan stok, dll).
                            // Ini lebih fleksibel daripada hanya menampilkan satu kolom dari relasi.
                            Forms\Components\Select::make('item_id')
                                ->label('Barang')
                                ->hiddenLabel()
                                ->relationship('item', 'name')
                                ->getOptionLabelFromRecordUsing(fn (Item $record) => "{$record->display_name} (SKU: {$record->sku}) - Stok: {$record->stock} {$record->unit}")
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated($updateItemData),
                            Forms\Components\Textarea::make('description')->hiddenLabel()->placeholder('Masukkan deskripsi barang')->rows(1)
                        ]),
                        Forms\Components\TextInput::make('quantity')
                            ->label(fn(Get $get) => 'Kuantitas' . ($get('unit_name') ? ' (' . $get('unit_name') . ')' : ''))
                            ->numeric()
                            ->step('0.01')
                            ->default(1.0)
                            ->required()
                            ->live()
                            ->rules([
                                function (Get $get, callable $set, $record, $operation) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $record, $operation) {
                                        $quantityInput = (float)$value;
                                        $itemId = $get('item_id');
                                        if ($quantityInput <= 0) {
                                            $fail("Kuantitas harus lebih dari 0.");
                                            return;
                                        }
                                        if (!$itemId) {
                                            return;
                                        }
                                        $currentInvoice = ($operation === 'edit' && $record instanceof Invoice) ? $record : null;
                                        $validation = self::validateStockAvailability($itemId, $quantityInput, $currentInvoice);
                                        if (!$validation['valid']) {
                                            $fail($validation['message']);
                                        }
                                    };
                                },
                            ])
                            ->suffix(fn(Get $get) => $get('unit_name') ? $get('unit_name') : null),
                        Forms\Components\TextInput::make('price')
                            ->label('Harga Satuan')
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                            ->prefix('Rp. ')
                            ->live()
                            ->required(),
                        Forms\Components\Hidden::make('unit_name')->dehydrated(false),
                        Forms\Components\Placeholder::make('subtotal_item')
                            ->hiddenLabel()
                            ->dehydrated(false)
                            ->extraAttributes(['class' => 'text-left md:text-center'])
                            ->content(function (Get $get) {
                                $quantity = (float)($get('quantity') ?? 0);
                                $price = self::parseCurrencyValue($get('price') ?? '0');
                                $total = $quantity * $price;
                                return self::formatCurrency($total);
                            }),
                    ])
                    ->footerItem(
                        fn(Get $get) => new HtmlString(
                            'Total: ' . self::formatCurrency(collect($get('invoiceItems'))->sum(function ($item) {
                                $quantity = (float)($item['quantity'] ?? 0.0);
                                $price = self::parseCurrencyValue($item['price'] ?? '0');
                                return $quantity * $price;
                            }))
                        )
                    )
                    ->extraItemActions([ // Menggunakan extraItemActions untuk action per item
                        Action::make('triggerSplitStockModal')
                            ->label('Pecah Stok')
                            ->icon('heroicon-o-arrows-up-down')
                            ->color('warning')
                            ->action(function () {
                                // Action ini hanya memicu modal.
                                // Data diakses via $arguments & $component dalam konfigurasi modal.
                            })
                            ->modalHeading(function (array $arguments, Forms\Components\Repeater $component) {
                                $itemRepeaterState = $component->getRawItemState($arguments['item']);
                                $childItemId = $itemRepeaterState['item_id'] ?? null;
                                return 'Pecah Stok untuk ' . ($childItemId ? Item::find($childItemId)?->name : 'Item Belum Dipilih');
                            })
                            ->modalWidth('lg')
                            ->form(function (array $arguments, Forms\Components\Repeater $component) {
                                $itemRepeaterState = $component->getRawItemState($arguments['item']);
                                $childItemId = $itemRepeaterState['item_id'] ?? null;
                                $childItem = $childItemId ? Item::find($childItemId) : null;

                                if (!$childItem) {
                                    return [
                                        Forms\Components\Placeholder::make('error_child_item_not_found')
                                            ->label('Error')
                                            ->content('Item eceran yang dipilih tidak ditemukan atau belum dipilih.'),
                                    ];
                                }
                                return [
                                    Forms\Components\Placeholder::make('child_item_info')
                                        ->label('Item yang Akan Ditambah Stoknya')
                                        ->content(fn () => "{$childItem->display_name} (Stok: {$childItem->stock} {$childItem->unit})"),

                                    Forms\Components\Select::make('from_item_id')
                                        ->label('Pilih Item Sumber (Induk)')
                                        ->options(function () use ($childItem) {
                                            return Item::where('product_id', $childItem->product_id)
                                                ->where('id', '!=', $childItem->id)
                                                ->where('stock', '>', 0)
                                                ->get()
                                                ->mapWithKeys(function ($parentItem) {
                                                    return [$parentItem->id => "{$parentItem->display_name} (Stok: {$parentItem->stock} {$parentItem->unit})"];
                                                });
                                        })
                                        ->required()
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state) use ($childItem) {
                                            $fromItemId = $state;
                                            $fromQuantityInput = $get('from_quantity');
                                            $sourceItem = $fromItemId ? Item::find($fromItemId) : null;

                                            if ($sourceItem && $childItem && $fromQuantityInput && is_numeric($fromQuantityInput) && (float)$fromQuantityInput > 0) {
                                                $calculated = ItemUnitConversionService::calculateTargetQuantity($sourceItem, $childItem, (float)$fromQuantityInput);
                                                $set('calculated_to_quantity', $calculated);
                                            } else {
                                                $set('calculated_to_quantity', null);
                                            }
                                            $set('to_quantity_unit_suffix', $childItem->unit);

                                            // Update max stock for validation
                                            if ($sourceItem) {
                                                $set('current_from_item_stock', $sourceItem->stock);
                                                if ($get('from_quantity') > $sourceItem->stock) {
                                                    $set('from_quantity', $sourceItem->stock);
                                                    // Recalculate if capped
                                                    $recalculated = ItemUnitConversionService::calculateTargetQuantity($sourceItem, $childItem, (float)$sourceItem->stock);
                                                    $set('calculated_to_quantity', $recalculated);
                                                }
                                            } else {
                                                $set('current_from_item_stock', null);
                                            }
                                        }),

                                    Forms\Components\Hidden::make('current_from_item_stock')->default(null),

                                    Forms\Components\TextInput::make('from_quantity')
                                        ->label('Jumlah Item Sumber yang Akan Dikonversi')
                                        ->numeric()
                                        ->default(1)
                                        ->required()
                                        ->minValue(1)
                                        ->maxValue(fn (Forms\Get $get) => $get('current_from_item_stock') ?? null)
                                        ->live()
                                        ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state) use ($childItem) {
                                            $fromItemId = $get('from_item_id');
                                            $fromQuantityInput = $state;
                                            $sourceItem = $fromItemId ? Item::find($fromItemId) : null;

                                            if ($sourceItem && $childItem && $fromQuantityInput && is_numeric($fromQuantityInput) && (float)$fromQuantityInput > 0) {
                                                $calculated = ItemUnitConversionService::calculateTargetQuantity($sourceItem, $childItem, (float)$fromQuantityInput);
                                                $set('calculated_to_quantity', $calculated);
                                            } else {
                                                $set('calculated_to_quantity', null);
                                            }
                                            $set('to_quantity_unit_suffix', $childItem->unit);
                                        }),

                                    Forms\Components\Placeholder::make('to_quantity_display')
                                        ->label('Jumlah Item yang Akan Dihasilkan')
                                        ->content(fn (Forms\Get $get) => $get('calculated_to_quantity') ? $get('calculated_to_quantity') . ' ' . $get('to_quantity_unit_suffix') : '-'),

                                    Forms\Components\Hidden::make('calculated_to_quantity')->default(null),
                                    Forms\Components\Hidden::make('to_quantity_unit_suffix')->default(null),

                                    Forms\Components\Textarea::make('notes')
                                        ->label('Catatan (Opsional)')
                                        ->rows(3),
                                ];
                            })
                            ->modalSubmitActionLabel('Lakukan Pecah Stok')
                            ->action(function (array $data, array $arguments, Forms\Components\Repeater $component) {
                                $itemRepeaterState = $component->getRawItemState($arguments['item']);
                                $childItem = Item::find($itemRepeaterState['item_id']);
                                $sourceParentItem = Item::find($data['from_item_id']);
                                $fromQuantity = (float)$data['from_quantity'];
                                $calculatedToQuantity = $data['calculated_to_quantity'];

                                if (!$childItem || !$sourceParentItem) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('Item tidak ditemukan.')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                if (is_null($calculatedToQuantity) || $calculatedToQuantity <= 0) {
                                    Notification::make()
                                        ->title('Konversi Stok Gagal')
                                        ->danger()
                                        ->body('Jumlah item yang dihasilkan tidak valid atau tidak dapat dihitung. Pastikan data volume item sumber dan tujuan sudah benar dan satuan volume standar sama.')
                                        ->send();
                                    return;
                                }

                                try {
                                    // Gunakan StockConversionService
                                    $stockConversionService = app(\App\Services\StockConversionService::class);

                                    $conversion = $stockConversionService->convertStock(
                                        fromItemId: $sourceParentItem->id,
                                        toItemId: $childItem->id,
                                        fromQuantity: $fromQuantity,
                                        toQuantity: $calculatedToQuantity,
                                        notes: $data['notes'] ?? "Pecah stok untuk invoice"
                                    );

                                    // Success notification
                                    Notification::make()
                                        ->title('Berhasil Pecah Stok')
                                        ->body("{$fromQuantity} {$sourceParentItem->unit} {$sourceParentItem->display_name} dipecah. Stok {$childItem->display_name} bertambah {$calculatedToQuantity} {$childItem->unit}.")
                                        ->success()
                                        ->send();

                                    // Trigger form refresh to show updated stock
                                    $component->state($component->getState());
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Gagal Pecah Stok')
                                        ->body('Terjadi kesalahan: ' . $e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            })->visible(function (array $arguments = [], $component = null): bool {
                                try {
                                    // Dapatkan UUID item dari arguments
                                    $itemUuid = $arguments['item'] ?? null;
                                    if (!$itemUuid) return false;

                                    // Gunakan component yang diteruskan sebagai parameter
                                    if (!$component) return false;

                                    // Dapatkan state item berdasarkan UUID
                                    $itemRepeaterState = $component->getRawItemState($itemUuid);
                                    $itemId = $itemRepeaterState['item_id'] ?? null;
                                    if (!$itemId) return false;

                                    $item = Item::find($itemId);
                                    if (!$item) return false;

                                    // Periksa apakah ada item lain dalam produk yang sama dengan stok > 0
                                    return Item::where('product_id', $item->product_id)
                                        ->where('id', '!=', $item->id)
                                        ->where('stock', '>', 0)
                                        ->exists();
                                } catch (\Exception $e) {
                                    // Jika terjadi error, sembunyikan tombol
                                    return false;
                                }
                            })
                    ])
                    ->columns(4) // Sesuaikan jumlah kolom jika perlu

            ]),

            // Bagian bawah untuk ringkasan biaya, ditata di sebelah kanan
            Section::make()->schema([
                Grid::make(2)->schema([
                    Group::make()->schema([
                        Forms\Components\Textarea::make('terms')->label('Syarat & Ketentuan')->rows(3),
                    ]),

                    // Grup untuk ringkasan biaya
                    Group::make()->schema([
                        Forms\Components\Placeholder::make('subtotal')
                            ->label('Subtotal')
                            ->extraAttributes(['class' => 'font-bold text-xl text-white'])
                            ->content(function (Get $get) {
                                // Hitung total dari services
                                $servicesTotal = collect($get('invoiceServices'))->sum(function ($service) {
                                    return self::parseCurrencyValue($service['price'] ?? '0');
                                });

                                // Hitung total dari items
                                $itemsTotal = collect($get('invoiceItems'))->sum(function ($item) {
                                    $quantity = (float)($item['quantity'] ?? 0.0); // Changed to float
                                    $price = self::parseCurrencyValue($item['price'] ?? '0');
                                    return $quantity * $price;
                                });

                                return self::formatCurrency($servicesTotal + $itemsTotal);
                            })
                            ->helperText('Total sebelum diskon & pajak.'),

                        // Grup untuk Diskon dengan pilihan Tipe
                        Grid::make(2)->schema([
                            Forms\Components\Select::make('discount_type')
                                ->label('Tipe Diskon')
                                ->options(['fixed' => 'Nominal (Rp)', 'percentage' => 'Persen (%)'])
                                ->default('fixed')
                                ->live(), // Changed to onBlur
                            Forms\Components\TextInput::make('discount_value')
                                ->label('Nilai Diskon')
                                ->default(0)
                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                ->prefix(fn(Get $get) => $get('discount_type') === 'fixed' ? 'Rp. ' : '% ')
                                ->live(), // Added debounce to reduce server load
                        ]),


                        // Total Akhir
                        Forms\Components\Placeholder::make('total_amount')
                            ->label('Total Akhir')
                            ->extraAttributes(['class' => 'font-bold text-xl text-green'])
                            ->Content(function (Get $get) {
                                // Hitung subtotal
                                $servicesTotal = collect($get('invoiceServices'))->sum(function ($service) {
                                    return self::parseCurrencyValue($service['price'] ?? '0');
                                });

                                $itemsTotal = collect($get('invoiceItems'))->sum(function ($item) {
                                    $quantity = (float)($item['quantity'] ?? 0); // Ubah dari int ke float
                                    $price = self::parseCurrencyValue($item['price'] ?? '0');
                                    return $quantity * $price;
                                });

                                $subtotal = $servicesTotal + $itemsTotal;

                                // Hitung diskon
                                $discountType = $get('discount_type') ?? 'fixed';
                                $discountValue = self::parseCurrencyValue($get('discount_value') ?? '0');

                                $discountAmount = 0;
                                if ($discountType === 'percentage') {
                                    $discountAmount = ($subtotal * $discountValue) / 100;
                                } else {
                                    $discountAmount = $discountValue;
                                }

                                $totalAmount = $subtotal - $discountAmount;
                                return self::formatCurrency($totalAmount);
                            }),
                    ]),
                ]),
            ]),

        ])->columns(1); // <-- KUNCI UTAMA: Mengubah layout menjadi 1 kolom
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->label('No. Invoice')->searchable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Pelanggan')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'unpaid' => 'Belum Dibayar',
                        'partially_paid' => 'Sebagian Dibayar',
                        'paid' => 'Lunas',
                        'overdue' => 'Terlambat',
                    })->badge()->color(fn(string $state): string => match ($state) {
                        'unpaid' => 'gray',
                        'partially_paid' => 'info',
                        'paid' => 'success',
                        'overdue' => 'danger',
                    })->searchable(),
                Tables\Columns\TextColumn::make('total_amount')->label('Total Biaya')->currency('IDR')->sortable(),
                Tables\Columns\TextColumn::make('invoice_date')->label('Tanggal Invoice')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('due_date')->label('Jatuh Tempo')->date('d M Y')->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                // Delete, ForceDelete, Restore actions moved to Infolist
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $stockService = app(\App\Services\InvoiceStockService::class);
                            foreach ($records as $record) {
                                $stockService->restoreStockForInvoiceItems($record);
                            }
                        }),
                    ForceDeleteBulkAction::make()
                        ->before(function (\Illuminate\Database\Eloquent\Collection $records) {
                            // Optional: Restore stock if business logic requires it for force delete
                            // $stockService = app(\App\Services\InvoiceStockService::class);
                            // foreach ($records as $record) {
                            //    $stockService->restoreStockForInvoiceItems($record);
                            // }
                        }),
                    RestoreBulkAction::make()
                        ->after(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $stockService = app(\App\Services\InvoiceStockService::class);
                            foreach ($records as $record) {
                                // Need to ensure items are loaded if they aren't by default on restored records in bulk
                                $record->loadMissing('items');
                                $itemsData = $record->items->map(function ($item) {
                                    return ['item_id' => $item->id, 'quantity' => $item->pivot->quantity];
                                })->toArray();
                                $stockService->deductStockForInvoiceItems($record, $itemsData);
                                // Also update status
                                \App\Traits\InvoiceCalculationTrait::updateInvoiceStatus($record);
                            }
                        }),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            // Removed the print route from here as it's a web route now
        ];
    }

    // Method to handle printing the invoice
    public function printInvoice(Invoice $record)
    {
        // Eager load relationships to prevent N+1 queries in the Blade view
        $record->load(['customer', 'vehicle', 'services', 'items.product']);
        return view('filament.resources.invoices.print', ['invoice' => $record]);
    }
}
