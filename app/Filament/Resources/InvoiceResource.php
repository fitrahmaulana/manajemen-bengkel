<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Forms\Components\CustomTableRepeater;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Service;
use App\Models\Vehicle;
use App\Traits\InvoiceCalculationTrait; // Added trait for optimized calculations
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
                // Repeater Jasa - Optimized untuk mengurangi lag
                CustomTableRepeater::make('services')
                    ->reorderAtStart()
                    ->hiddenLabel()
                    ->excludeAttributesForCloning(['id', 'invoice_id', 'created_at']) //
                    ->footerItem(
                        fn(Get $get) => new HtmlString(
                            'Total: ' . self::formatCurrency(collect($get('services'))->sum(function ($service) {
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
                        // group
                        Forms\Components\Group::make()->schema([
                            Forms\Components\Select::make('service_id')
                                ->hiddenLabel()
                                ->options(Service::all()->pluck('name', 'id'))
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
                            ->dehydrated(false) // Tidak disimpan ke database
                            ->extraAttributes(['class' => 'text-left md:text-center'])
                            ->content(function (Get $get) {
                                $price = self::parseCurrencyValue($get('price') ?? '0');
                                return self::formatCurrency($price);
                            }),
                    ])
                    ->columns(3),

                // Repeater Barang
                CustomTableRepeater::make('items')
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
                            Forms\Components\Select::make('item_id')
                                ->label('Barang')
                                ->hiddenLabel()
                                ->options(function () {
                                    return Item::query()
                                        ->with(['product'])
                                        ->get()
                                        ->mapWithKeys(function ($item) {
                                            // Menampilkan nama produk + varian (jika ada) + SKU
                                            $productName = $item->product->name;
                                            $variantName = $item->name;

                                            // Untuk produk tanpa varian (name kosong), tampilkan hanya nama produk
                                            if (empty($variantName)) {
                                                $displayName = $productName;
                                            } else {
                                                // Untuk produk dengan varian, tampilkan nama produk + varian
                                                $displayName = $productName . ' ' . $variantName;
                                            }

                                            $skuInfo = $item->sku ? " (SKU: " . $item->sku . ")" : "";
                                            return [$item->id => $displayName . $skuInfo];
                                        });
                                })
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated($updateItemData),
                            Forms\Components\Textarea::make('description')->hiddenLabel()->placeholder('Masukkan deskripsi barang')->rows(1)
                        ]),
                        Forms\Components\TextInput::make('quantity')
                            ->label(fn(Get $get) => 'Kuantitas' . ($get('unit_name') ? ' (' . $get('unit_name') . ')' : ''))
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->live()
                            ->rules([
                                function (Get $get, callable $set, $record, $operation) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $record, $operation) {
                                        $quantityInput = (int)$value;
                                        $itemId = $get('item_id');

                                        if ($quantityInput <= 0) {
                                            $fail("Kuantitas harus lebih dari 0.");
                                            return;
                                        }

                                        if (!$itemId) {
                                            return; // Item not selected yet
                                        }

                                        // Use optimized validation from trait
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
                        Forms\Components\Hidden::make('unit_name'),
                        Forms\Components\Placeholder::make('subtotal_item')
                            ->hiddenLabel()
                            ->dehydrated(false) // Tidak disimpan ke database
                            ->extraAttributes(['class' => 'text-left md:text-center'])
                            ->content(function (Get $get) {
                                $quantity = (int)($get('quantity') ?? 0);
                                $price = self::parseCurrencyValue($get('price') ?? '0');
                                $total = $quantity * $price;
                                return self::formatCurrency($total);
                            }),
                    ])
                    ->footerItem(
                        fn(Get $get) => new HtmlString(
                            'Total: ' . self::formatCurrency(collect($get('items'))->sum(function ($item) {
                                $quantity = (int)($item['quantity'] ?? 0);
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
                                    Forms\Components\Select::make('source_parent_item_id')
                                        ->label('Pilih Item Induk')
                                        ->options(function () use ($childItem) {
                                            return $childItem->sourceParents()
                                                ->where('stock', '>', 0)
                                                ->get()
                                                ->mapWithKeys(function ($parentItem) {
                                                    return [$parentItem->id => "{$parentItem->name} (Stok: {$parentItem->stock})"];
                                                });
                                        })
                                        ->required()
                                        ->live(onBlur: true)
                                        ->helperText('Pilih item yang akan dipecah untuk menambah stok ' . $childItem->name),
                                    Forms\Components\TextInput::make('parent_quantity_to_split')
                                        ->label('Jumlah yang Dipecah')
                                        ->numeric()
                                        ->minValue(1)
                                        ->required()
                                        ->live(onBlur: true)
                                        ->helperText('Masukkan jumlah unit yang akan dipecah')
                                        ->rules([
                                            function (Get $get, callable $set) {
                                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                    $sourceParentItemId = $get('source_parent_item_id');
                                                    if (!$sourceParentItemId) {
                                                        $fail("Item induk harus dipilih.");
                                                        return;
                                                    }

                                                    $sourceParentItem = Item::find($sourceParentItemId);
                                                    if (!$sourceParentItem) {
                                                        $fail("Item tidak valid.");
                                                        return;
                                                    }

                                                    if ($value > $sourceParentItem->stock) {
                                                        $fail("Stok tidak cukup. Tersedia: {$sourceParentItem->stock}");
                                                        return;
                                                    }

                                                    // Quick validation
                                                    $result = $value * $sourceParentItem->conversion_value;
                                                    if ($result <= 0) {
                                                        $fail("Error konversi. Hubungi admin.");
                                                        return;
                                                    }
                                                };
                                            },
                                        ]),
                                ];
                            })
                            ->modalSubmitActionLabel('Lakukan Pecah Stok')
                            ->action(function (array $data, array $arguments, Forms\Components\Repeater $component) {
                                // No validation needed - already handled by form rules!
                                // Direct business logic execution

                                $itemRepeaterState = $component->getRawItemState($arguments['item']);
                                $childItem = Item::find($itemRepeaterState['item_id']);
                                $sourceParentItem = Item::find($data['source_parent_item_id']);
                                $parentQuantityToSplit = (int)$data['parent_quantity_to_split'];
                                $generatedChildQuantity = $parentQuantityToSplit * $sourceParentItem->conversion_value;

                                try {
                                    DB::transaction(function () use ($sourceParentItem, $childItem, $parentQuantityToSplit, $generatedChildQuantity) {
                                        $sourceParentItem->decrement('stock', $parentQuantityToSplit);
                                        $childItem->increment('stock', $generatedChildQuantity);
                                    });

                                    // Success notification
                                    Notification::make()
                                        ->title('Berhasil Pecah Stok')
                                        ->body("{$parentQuantityToSplit} {$sourceParentItem->unit} {$sourceParentItem->name} dipecah. Stok {$childItem->name} bertambah {$generatedChildQuantity} {$childItem->unit}.")
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
                            })->visible(function ($state): bool {
                                $itemRepeaterState = $state ?? [];
                                $itemId = $itemRepeaterState['item_id'] ?? null;
                                dd($itemId, $itemRepeaterState);
                                if (!$itemId) return false;

                                $item = Item::find($itemId);
                                if (!$item || $item->is_convertible) {
                                    return false;
                                }

                                $quantityNeeded = (int)($itemRepeaterState['quantity'] ?? 0);
                                if ($item->stock >= $quantityNeeded) {
                                    return false;
                                }
                                return $item->sourceParents()->where('stock', '>', 0)->exists();
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
                            ->content(function (Get $get) {
                                // Hitung total dari services
                                $servicesTotal = collect($get('services'))->sum(function ($service) {
                                    return self::parseCurrencyValue($service['price'] ?? '0');
                                });

                                // Hitung total dari items
                                $itemsTotal = collect($get('items'))->sum(function ($item) {
                                    $quantity = (int)($item['quantity'] ?? 0);
                                    $price = self::parseCurrencyValue($item['price'] ?? '0');
                                    return $quantity * $price;
                                });

                                $subtotal = $servicesTotal + $itemsTotal;
                                return self::formatCurrency($subtotal);
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
                            ->content(function (Get $get) {
                                // Hitung subtotal
                                $servicesTotal = collect($get('services'))->sum(function ($service) {
                                    return self::parseCurrencyValue($service['price'] ?? '0');
                                });

                                $itemsTotal = collect($get('items'))->sum(function ($item) {
                                    $quantity = (int)($item['quantity'] ?? 0);
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
                    Tables\Actions\DeleteBulkAction::make(), // Will now soft delete
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
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
