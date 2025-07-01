<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Service;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope; // Required for soft delete features
use Filament\Tables\Filters\TrashedFilter; // Required for TrashedFilter
use Filament\Tables\Actions\ForceDeleteBulkAction; // Required for bulk force delete
use Filament\Tables\Actions\RestoreBulkAction; // Required for bulk restore
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use App\Filament\Resources\InvoiceResource\RelationManagers\PaymentRelationManager;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Faktur';
    protected static ?string $modelLabel = 'Faktur';
    protected static ?string $pluralModelLabel = 'Daftar Faktur';
    protected static ?int $navigationSort = 1;



    public static function form(Form $form): Form
    {
        // Fungsi kalkulasi tetap sama, tidak perlu diubah
        $calculateTotals = function (Get $get, Set $set) {
            $servicesData = $get('services') ?? [];
            $itemsData = $get('items') ?? [];

            $subtotal = 0;
            // Calculate subtotal from services
            foreach ($servicesData as $service) {
                // Ensure price is treated as a float, accounting for currency mask with space
                $price = isset($service['price']) ? (float)str_replace(['Rp. ', '.'], ['', ''], (string)$service['price']) : 0;
                if (!empty($service['service_id'])) {
                    // Assuming quantity is not masked, default to 1 if not set
                    $subtotal += $price;
                }
            }

            // Calculate subtotal from items
            foreach ($itemsData as $item) {
                // Ensure price and quantity are treated as floats/integers
                $price = isset($item['price']) ? (float)str_replace(['Rp. ', '.'], ['', ''], (string)$item['price']) : 0;
                $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1; // Assuming quantity is not masked
                if (!empty($item['item_id'])) {
                    $subtotal += $price * $quantity;
                }
            }

            // discount_value is also a masked input now
            $discountInput = $get('discount_value') ?? '0';
            $discountValue = (float)str_replace(['Rp. ', '.'], ['', ''], (string)$discountInput);

            $finalDiscount = 0;
            if ($get('discount_type') === 'percentage' && $discountValue > 0) {
                $finalDiscount = ($subtotal * $discountValue) / 100;
            } else {
                $finalDiscount = $discountValue; // This is the actual numeric value of discount
            }

            $total = $subtotal - $finalDiscount;

            $set('subtotal', $subtotal);
            $set('total_amount', $total);
        };


        return $form->schema([
            // Bagian atas untuk detail invoice dan pelanggan
            Section::make()->schema([
                Grid::make(3)->schema([
                    Group::make()->schema([
                        Forms\Components\Select::make('customer_id')->relationship('customer', 'name')->label('Pelanggan')->searchable()->preload()->required()->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                // Reset vehicle_id when customer changes
                                $set('vehicle_id', null);
                            }),
                        Forms\Components\Select::make('vehicle_id')->label('Kendaraan (No. Polisi)')->options(fn(Get $get) => Vehicle::query()->where('customer_id', $get('customer_id'))->pluck('license_plate', 'id'))->searchable()->preload()->required(),
                    ]),
                    Group::make()->schema([
                        Forms\Components\TextInput::make('invoice_number')->label('Nomor Invoice')->default('INV-' . date('Ymd-His'))->required(),
                        Forms\Components\Select::make('status')->options(['draft' => 'Draft', 'sent' => 'Terkirim', 'paid' => 'Lunas', 'overdue' => 'Jatuh Tempo',])->default('draft')->required(),
                    ]),
                    Group::make()->schema([
                        Forms\Components\DatePicker::make('invoice_date')->label('Tanggal Invoice')->default(now())->required(),
                        Forms\Components\DatePicker::make('due_date')->label('Tanggal Jatuh Tempo')->default(now()->addDays(7))->required(),
                    ]),
                ]),
            ]),

            // Bagian Repeater yang sekarang dibuat lebih lebar
            Section::make('Detail Pekerjaan & Barang')->schema([
                // Repeater Jasa
                Forms\Components\Repeater::make('services')
                    ->label('Jasa / Layanan')->schema([
                        Forms\Components\Select::make('service_id')
                            ->label('Jasa')
                            ->options(Service::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                $service = Service::find($state);
                                $set('price', $service?->price ?? 0);
                            }),
                        Forms\Components\TextInput::make('price')
                            ->label('Harga Jasa')
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                            ->prefix('Rp. ') // Note the space
                            ->live(debounce: 500)
                            ->required(), // Made editable, so likely required
                        Forms\Components\Textarea::make('description')->label('Deskripsi')->rows(1),
                    ])
                    ->columns(3)
                    ->live()
                    ->afterStateUpdated($calculateTotals),

                // Repeater Barang
                Forms\Components\Repeater::make('items')
                    ->label('Barang / Suku Cadang')
                    ->itemLabel(function (array $state): ?string {
                        if (empty($state['item_id'])) {
                            return null;
                        }
                        // Ambil data item terbaru dari database untuk memastikan stok akurat
                        $item = Item::find($state['item_id']);
                        if (!$item) {
                            return 'Item tidak ditemukan';
                        }
                        return $item->name . ' (Stok: ' . $item->stock . ' ' . $item->unit . ')';
                    })
                    ->schema([
                        Forms\Components\Select::make('item_id')
                            ->label('Barang')
                            ->options(function () {
                                return Item::query()
                                    ->get()
                                    ->mapWithKeys(function ($item) {
                                        // Menampilkan SKU bukan Stok di opsi Select
                                        $skuInfo = $item->sku ? " (SKU: " . $item->sku . ")" : "";
                                        return [$item->id => $item->name . $skuInfo];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                $item = Item::find($state);
                                $set('price', $item?->selling_price ?? 0);
                                $set('unit_name', $item?->unit ?? null);
                            }),
                        Forms\Components\TextInput::make('quantity')
                            ->label(fn(Get $get) => 'Kuantitas' . ($get('unit_name') ? ' (' . $get('unit_name') . ')' : ''))
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->live(onBlur: true)
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

                                        $item = Item::find($itemId);
                                        if (!$item) {
                                            $fail("Item tidak valid.");
                                            return;
                                        }

                                        $currentInvoiceRecord = $record;
                                        $originalQuantityOnInvoice = 0;
                                        $isEditOperation = $operation === 'edit' && $currentInvoiceRecord instanceof Invoice;
                                        if ($isEditOperation) {
                                            // Get the original item quantity from the invoice being edited
                                            $originalItemOnInvoice = $currentInvoiceRecord->items()->where('item_id', $itemId)->first();
                                            if ($originalItemOnInvoice) {
                                                $originalQuantityOnInvoice = $originalItemOnInvoice->pivot->quantity;
                                            }
                                        }

                                        $neededStock = 0;
                                        if ($isEditOperation) {
                                            // For edits, only check stock for the *increase* in quantity
                                            $quantityDifference = $quantityInput - $originalQuantityOnInvoice;
                                            if ($quantityDifference > 0) {
                                                $neededStock = $quantityDifference;
                                            } else {
                                                // Quantity decreased or stayed the same, no additional stock needed
                                                return;
                                            }
                                        } else {
                                            // For new items (on create page or new item in repeater on edit page)
                                            // Check stock for the full quantity
                                            $neededStock = $quantityInput;
                                        }

                                        // jika stok yang dibutuhkan lebih dari 0 dan jika stok item tidak cukup lakukan validasi
                                        if ($neededStock > 0 && $item->stock < $neededStock) {
                                            $hasPotentialToSplit = false;
                                            if (!$item->is_convertible) {
                                                $hasPotentialToSplit = $item->sourceParents()->where('stock', '>', 0)->exists();
                                            }

                                            if ($hasPotentialToSplit) {
                                                //lakukan validasi jika stok eceran tidak cukup untuk melakukan pecah stok
                                                $fail("Stok {$item->name} tidak cukup untuk menambah {$neededStock} {$item->unit}, silakan gunakan opsi 'Pecah Stok' untuk mengatasi masalah ini.");
                                            }

                                            if (!$hasPotentialToSplit) {
                                                if ($isEditOperation) {
                                                    $fail("Stok {$item->name} tidak cukup untuk menambah {$neededStock} {$item->unit} (stok saat ini: {$item->stock} {$item->unit}, sudah ada {$originalQuantityOnInvoice} {$item->unit} di faktur ini).");
                                                } else {
                                                    $fail("Stok {$item->name} hanya {$item->stock} {$item->unit}. Kuantitas ({$quantityInput} {$item->unit}) melebihi stok yang tersedia dan tidak ada opsi pecah stok.");
                                                }
                                            }
                                            // If potential to split, validation passes here, relying on user to use split action
                                        }
                                    };
                                },
                            ])
                            ->suffix(fn(Get $get) => $get('unit_name') ? $get('unit_name') : null),
                        Forms\Components\TextInput::make('price')
                            ->label('Harga Satuan')
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                            ->prefix('Rp. ') // Note the space
                            ->live(debounce: 500)
                            ->required(),
                        Forms\Components\Hidden::make('unit_name'),
                        Forms\Components\Textarea::make('description')->label('Deskripsi')->rows(1)
                    ])
                    ->extraItemActions([ // Menggunakan extraItemActions untuk action per item
                        Action::make('triggerSplitStockModal')
                            ->label('Pecah Stok')
                            ->icon('heroicon-o-arrows-up-down')
                            ->color('warning')
                            ->action(function (array $arguments, Forms\Components\Repeater $component) {
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
                                $quantityInForm = (int)($itemRepeaterState['quantity'] ?? 0);
                                $quantityActuallyNeeded = max(0, $quantityInForm - $childItem->stock);

                                return [
                                    Forms\Components\Placeholder::make('info')
                                        ->label('Informasi Kebutuhan')
                                        ->content("Anda membutuhkan tambahan {$quantityActuallyNeeded} {$childItem->unit} untuk item {$childItem->name} (Kuantitas di form: {$quantityInForm}, Stok saat ini: {$childItem->stock} {$childItem->unit})."),
                                    Forms\Components\Select::make('source_parent_item_id')
                                        ->label('Pilih Item Induk untuk Dipecah')
                                        ->options(function () use ($childItem) {
                                            return $childItem->sourceParents()
                                                ->where('stock', '>', 0)
                                                ->get()
                                                ->mapWithKeys(function ($parentItem) {
                                                    $targetChildUnit = $parentItem->targetChild?->unit ?? 'eceran';
                                                    return [$parentItem->id => "{$parentItem->name} (Stok: {$parentItem->stock} {$parentItem->unit}, 1 {$parentItem->unit} = {$parentItem->conversion_value} {$targetChildUnit})"];
                                                });
                                        })
                                        ->required()
                                        ->live(),
                                    Forms\Components\TextInput::make('parent_quantity_to_split')
                                        ->label('Jumlah Unit Induk yang Akan Dipecah')
                                        ->numeric()
                                        ->minValue(1)
                                        ->required()
                                        ->live(onBlur: true) // Tetap live jika ingin ada interaksi lain nanti
                                        ->helperText('Pastikan jumlah tidak melebihi stok item induk yang dipilih.')
                                        ->rules([
                                            function (Get $get, callable $set) {
                                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                    $sourceParentItemId = $get('source_parent_item_id');
                                                    if (!$sourceParentItemId) {
                                                        $fail("Item induk yang akan dipecah harus dipilih.");
                                                        return;
                                                    }

                                                    $sourceParentItem = Item::find($sourceParentItemId);
                                                    if ($value > $sourceParentItem->stock) {
                                                        $fail("Jumlah unit induk yang akan dipecah melebihi stok yang tersedia.");
                                                        return;
                                                    }
                                                };
                                            },
                                        ]),
                                ];
                            })
                            ->modalSubmitActionLabel('Lakukan Pecah Stok')
                            ->action(function (array $data, array $arguments, Forms\Components\Repeater $component) {
                                $itemRepeaterState = $component->getRawItemState($arguments['item']); // Validated state
                                $childItemId = $itemRepeaterState['item_id'] ?? null;
                                $childItem = $childItemId ? Item::find($childItemId) : null;

                                $sourceParentItemId = $data['source_parent_item_id'] ?? null; // Ambil dari data modal
                                $sourceParentItem = $sourceParentItemId ? Item::find($sourceParentItemId) : null;
                                $parentQuantityToSplit = (int)($data['parent_quantity_to_split'] ?? 0);

                                if (!$childItem || !$sourceParentItem || $parentQuantityToSplit <= 0) {
                                    Notification::make()->title('Error')->body('Data tidak valid untuk proses pecah stok. Pastikan item induk dan jumlah dipilih dengan benar.')->danger()->send();
                                    return;
                                }

                                // Validasi stok induk dilakukan di sini, sebelum transaksi
                                if ($parentQuantityToSplit > $sourceParentItem->stock) {
                                    Notification::make()->title('Stok Induk Tidak Cukup')->body("Stok " . ($sourceParentItem->name) . " hanya " . ($sourceParentItem->stock) . " unit.")->danger()->send();
                                    return;
                                }

                                // Hitung generatedChildQuantity di luar transaksi agar bisa di-use dan untuk notifikasi
                                $generatedChildQuantity = $parentQuantityToSplit * $sourceParentItem->conversion_value;
                                if (!is_numeric($generatedChildQuantity) || $generatedChildQuantity < 0) {
                                    Notification::make()->title('Error Kalkulasi')->body('Gagal menghitung jumlah item hasil konversi. Periksa nilai konversi item induk.')->danger()->send();
                                    return;
                                }

                                try {
                                    DB::transaction(function () use ($sourceParentItem, $childItem, $parentQuantityToSplit, $generatedChildQuantity) {
                                        $sourceParentItem->decrement('stock', $parentQuantityToSplit);
                                        $childItem->increment('stock', $generatedChildQuantity);
                                    });

                                    // Notifikasi hanya dikirim sekali setelah transaksi berhasil
                                    Notification::make()->title('Berhasil Pecah Stok')->success()
                                        ->body("{$parentQuantityToSplit} {$sourceParentItem->unit} {$sourceParentItem->name} dipecah. Stok {$childItem->name} bertambah {$generatedChildQuantity} {$childItem->unit}.")
                                        ->send();

                                    $currentState = $component->getState();
                                    $component->state($currentState); // Ini akan memicu refresh

                                } catch (\Exception $e) {
                                    // Notifikasi error jika transaksi gagal
                                    Notification::make()->title('Gagal Pecah Stok')->body('Terjadi kesalahan internal saat memproses pecah stok: ' . $e->getMessage())->danger()->send();
                                }
                            })
                            ->visible(function (array $arguments, Forms\Components\Repeater $component): bool {
                                $itemRepeaterState = $component->getRawItemState($arguments['item']);
                                $itemId = $itemRepeaterState['item_id'] ?? null;

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
                            }),
                    ])
                    ->columns(4) // Sesuaikan jumlah kolom jika perlu
                    ->live()
                    ->afterStateUpdated($calculateTotals),
            ]),

            // Bagian bawah untuk ringkasan biaya, ditata di sebelah kanan
            Section::make()->schema([
                Grid::make(2)->schema([
                    Group::make()->schema([
                        Forms\Components\Textarea::make('terms')->label('Syarat & Ketentuan')->rows(3),
                    ]),

                    // Grup untuk ringkasan biaya
                    Group::make()->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                            ->prefix('Rp. ') // Note the space
                            ->readOnly()
                            ->helperText('Total sebelum diskon & pajak.'),

                        // Grup untuk Diskon dengan pilihan Tipe
                        Grid::make(2)->schema([
                            Forms\Components\Select::make('discount_type')
                                ->label('Tipe Diskon')
                                ->options(['fixed' => 'Nominal (Rp)', 'percentage' => 'Persen (%)'])
                                ->default('fixed')->live(debounce: 600),
                            Forms\Components\TextInput::make('discount_value')
                                ->label('Nilai Diskon')
                                ->default(0)
                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                ->prefix('Rp. ') // Note the space
                                ->live(debounce: 600)
                                ->afterStateUpdated($calculateTotals),
                        ]),


                        // Total Akhir
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Akhir')
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                            ->prefix('Rp. ') // Note the space
                            ->readOnly(),
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
                Tables\Columns\TextColumn::make('status')->badge()->searchable(),
                Tables\Columns\TextColumn::make('total_amount')->label('Total Biaya')->currency('IDR')->sortable(),
                Tables\Columns\TextColumn::make('invoice_date')->label('Tanggal Invoice')->date('d M Y')->sortable(),
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

use App\Filament\Resources\InvoiceResource\RelationManagers\PaymentRelationManager;

    public static function getRelations(): array
    {
        return [
            PaymentRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ActionGroup removed from here

                // === BAGIAN ATAS: DETAIL PELANGGAN & FAKTUR ===
                Infolists\Components\Section::make('Informasi Faktur')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\Group::make()->schema([
                                    Infolists\Components\TextEntry::make('customer.name')->label('Pelanggan'),
                                    Infolists\Components\TextEntry::make('vehicle.license_plate')->label('No. Polisi'),
                                    Infolists\Components\TextEntry::make('vehicle.brand')->label('Merk Kendaraan'),
                                ]),
                                Infolists\Components\Group::make()->schema([
                                    Infolists\Components\TextEntry::make('invoice_number')->label('No. Invoice'),
                                    Infolists\Components\TextEntry::make('status')->badge()->color(fn(string $state): string => match ($state) {
                                        'draft' => 'gray',
                                        'sent' => 'info',
                                        'paid' => 'success',
                                        'overdue' => 'danger',
                                    }),
                                ]),
                                Infolists\Components\Group::make()->schema([
                                    Infolists\Components\TextEntry::make('invoice_date')->label('Tanggal Invoice')->date('d M Y'),
                                    Infolists\Components\TextEntry::make('due_date')->label('Tanggal Jatuh Tempo')->date('d M Y'),
                                ]),
                            ]),
                    ]),

                // === BAGIAN TENGAH: DAFTAR JASA & BARANG ===
                Infolists\Components\Section::make('Detail Jasa / Layanan')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('services')
                            ->hiddenLabel()
                            ->schema([
                                Infolists\Components\TextEntry::make('name')->label('Nama Jasa')->weight('bold'),
                                Infolists\Components\TextEntry::make('pivot.description')->label('Deskripsi')->placeholder('Tidak ada deskripsi.'),
                                Infolists\Components\TextEntry::make('pivot.price')->label('Biaya')->currency('IDR'),
                            ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Detail Barang / Suku Cadang')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->schema([
                                Infolists\Components\TextEntry::make('name')->label('Nama Barang')->weight('bold')->columnSpan(2),
                                Infolists\Components\TextEntry::make('pivot.quantity')
                                    ->label('Kuantitas')
                                    ->formatStateUsing(function ($record) {
                                        $unit = property_exists($record, 'unit') && $record->unit ? ' ' . $record->unit : '';
                                        return ($record->pivot->quantity ?? '') . $unit;
                                    }),
                                Infolists\Components\TextEntry::make('pivot.price')->label('Harga Satuan')->currency('IDR'),
                                // Menghitung subtotal per item
                                Infolists\Components\TextEntry::make('sub_total_calculated') // Renamed key
                                    ->label('Subtotal')
                                    ->currency('IDR')
                                    ->state(fn($record): float => ($record->pivot->quantity ?? 0) * ($record->pivot->price ?? 0)),
                                Infolists\Components\TextEntry::make('pivot.description')->label('Deskripsi')->columnSpanFull()->placeholder('Tidak ada deskripsi.'),

                            ])->columns(5),
                    ]),

                // === BAGIAN BAWAH: RINGKASAN BIAYA ===
                Infolists\Components\Section::make('Ringkasan Biaya')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                // Kolom Kiri: Syarat & Ketentuan
                                Infolists\Components\Group::make()->schema([
                                    Infolists\Components\TextEntry::make('terms')
                                        ->label('Syarat & Ketentuan')
                                        ->placeholder('Tidak ada syarat & ketentuan.'),
                                ]),
                                // Kolom Kanan: Kalkulasi
                                Infolists\Components\Group::make()->schema([
                                    Infolists\Components\TextEntry::make('subtotal')->currency('IDR'),
                                    Infolists\Components\TextEntry::make('discount_value')
                                        ->label('Diskon')
                                        ->formatStateUsing(function ($record) {
                                            if ($record->discount_type === 'percentage') {
                                                return ($record->discount_value ?? 0) . '%';
                                            }
                                            // For fixed, rely on ->currency('IDR') by setting state
                                            return $record->discount_value;
                                        })
                                        ->currency(fn($record) => $record->discount_type === 'fixed' ? 'IDR' : null) // Apply currency only if fixed
                                        ->suffix(fn($record) => $record->discount_type === 'percentage' ? '%' : null), // Keep suffix for percentage
                                    Infolists\Components\TextEntry::make('total_amount')
                                        ->label('Total Akhir')
                                        ->currency('IDR')
                                        ->weight('bold')
                                        ->size('lg'),
                                    Infolists\Components\TextEntry::make('total_paid_amount')
                                        ->label('Total Dibayar')
                                        ->currency('IDR')
                                        ->state(fn ($record) => $record->total_paid_amount) // Use the accessor
                                        ->weight('semibold'),
                                    Infolists\Components\TextEntry::make('balance_due')
                                        ->label('Sisa Tagihan')
                                        ->currency('IDR')
                                        ->state(fn ($record) => $record->balance_due) // Use the accessor
                                        ->weight('bold')
                                        ->color(fn ($record) => $record->balance_due > 0 ? 'warning' : 'success')
                                        ->size('lg'),
                                ]), // <-- Mendorong grup ini ke kanan
                            ]),
                    ]),

                // Payment history is now handled by PaymentRelationManager
            ]);
    }
}
