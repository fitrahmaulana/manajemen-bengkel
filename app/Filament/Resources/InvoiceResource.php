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
                    ->label('Barang / Suku Cadang')->schema([
                        Forms\Components\Select::make('item_id')
                            ->label('Barang')
                            ->options(function () {
                                return Item::query()
                                    ->get()
                                    ->mapWithKeys(function ($item) {
                                        // Formatnya: "Nama Barang (Stok: X Unit)"
                                        $stockInfo = " (Stok: " . ($item->stock ?? 0) . " " . $item->unit . ")";
                                        // Return the formatted name with stock info
                                        return [$item->id => $item->name . $stockInfo];
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
                            ->live(debounce: 500)
                            // Validasi kuantitas akan disesuaikan di langkah berikutnya atau saat implementasi modal
                            // Untuk saat ini, kita biarkan validasi standar, atau bisa dimodifikasi agar tidak terlalu ketat jika ada opsi pecah stok
                            ->rules([
                                function (Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $itemId = $get('item_id');
                                        if (!$itemId) return;
                                        $item = Item::find($itemId);
                                        if (!$item) return;

                                        // Jika item adalah eceran dan stoknya kurang, jangan langsung fail jika ada potensi pecah stok
                                        // Kondisi ini akan disempurnakan saat modal pecah stok diimplementasikan
                                        $potentialToSplit = false;
                                        if (!$item->is_convertible) { // Ini item eceran
                                            $sourceParents = $item->sourceParents()->where('stock', '>', 0)->exists();
                                            if ($sourceParents) {
                                                $potentialToSplit = true;
                                            }
                                        }

                                        if (!$potentialToSplit && (int)$value > $item->stock) {
                                            Notification::make()
                                                ->title('Stok Tidak Cukup')
                                                ->body("Stok {$item->name} hanya tersisa {$item->stock} {$item->unit}.")
                                                ->danger()
                                                ->send();
                                            $fail("Stok {$item->name} hanya {$item->stock} {$item->unit}. Kuantitas tidak boleh melebihi stok yang tersedia tanpa opsi pecah stok.");
                                        } elseif ((int)$value <= 0) {
                                            $fail("Kuantitas harus lebih dari 0.");
                                        }
                                        // Jika ada potensi pecah stok, validasi akan ditangani lebih lanjut oleh mekanisme pecah stok
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
                        Forms\Components\Textarea::make('description')->label('Deskripsi')->rows(1)->columnSpan(3), // description mengambil 3 kolom
                        // Tombol aksi akan ditambahkan di sini, di sebelah kanan deskripsi atau di bawahnya
                        // Kita akan letakkan di sebelah tombol delete bawaan jika memungkinkan, atau sebagai action di dalam item repeater
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
                            ->modalHeading(function(array $arguments, Forms\Components\Repeater $component) {
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
                                        ->live(onBlur: true)
                                        ->helperText(function (Get $modalGet) {
                                            $sourceParentItemId = $modalGet('source_parent_item_id');
                                            $parentQuantity = (int)$modalGet('parent_quantity_to_split');

                                            // Hanya proses jika kedua field memiliki nilai yang valid
                                            if ($sourceParentItemId && $parentQuantity > 0) {
                                                $parentItem = Item::find($sourceParentItemId);
                                                // Pastikan parentItem dan targetChild ada sebelum mengakses propertinya
                                                if ($parentItem && $parentItem->targetChild && is_numeric($parentItem->conversion_value)) {
                                                    $generatedChildQty = $parentQuantity * $parentItem->conversion_value;
                                                    return "Akan menghasilkan: {$generatedChildQty} {$parentItem->targetChild->unit}";
                                                }
                                            }
                                            return null; // Default jika kondisi tidak terpenuhi
                                        })
                                        ->rules([
                                            fn(Get $modalGet): \Closure => function (string $attribute, $value, \Closure $fail) use ($modalGet) {
                                                $sourceParentItemId = $modalGet('source_parent_item_id');
                                                if (!$sourceParentItemId) return;
                                                $parentItem = Item::find($sourceParentItemId);
                                                if (!$parentItem) {
                                                    $fail("Item induk tidak ditemukan.");
                                                    return;
                                                }
                                                if ((int)$value > $parentItem->stock) {
                                                    $fail("Stok item induk ({$parentItem->name}) hanya tersisa {$parentItem->stock} {$parentItem->unit}.");
                                                }
                                            }
                                        ]),
                                ];
                            })
                            ->modalSubmitActionLabel('Lakukan Pecah Stok')
                            ->action(function (array $data, array $arguments, Forms\Components\Repeater $component) {
                                $itemRepeaterState = $component->getItemState($arguments['item']); // Validated state
                                $childItemId = $itemRepeaterState['item_id'] ?? null;
                                $childItem = $childItemId ? Item::find($childItemId) : null;

                                $sourceParentItem = Item::find($data['source_parent_item_id']);
                                $parentQuantityToSplit = (int)$data['parent_quantity_to_split'];

                                if (!$childItem || !$sourceParentItem || $parentQuantityToSplit <= 0) {
                                    Notification::make()->title('Error')->body('Data tidak valid untuk proses pecah stok.')->danger()->send();
                                    return;
                                }

                                if ($parentQuantityToSplit > $sourceParentItem->stock) {
                                    Notification::make()->title('Stok Induk Tidak Cukup')->body("Stok {$sourceParentItem->name} hanya {$sourceParentItem->stock} unit.")->danger()->send();
                                    return;
                                }

                                try {
                                    DB::transaction(function () use ($sourceParentItem, $childItem, $parentQuantityToSplit) {
                                        $sourceParentItem->decrement('stock', $parentQuantityToSplit);
                                        $generatedChildQuantity = $parentQuantityToSplit * $sourceParentItem->conversion_value;
                                        $childItem->increment('stock', $generatedChildQuantity);
                                    });

                                    Notification::make()->title('Berhasil Pecah Stok')->success()
                                        ->body("{$parentQuantityToSplit} {$sourceParentItem->unit} {$sourceParentItem->name} dipecah. Stok {$childItem->name} bertambah {$generatedChildQuantity} {$childItem->unit}.")
                                        ->send();

                                    // Refresh repeater state
                                    $currentState = $component->getState();
                                    $component->state($currentState); // Ini akan memicu refresh

                                } catch (\Exception $e) {
                                    Notification::make()->title('Gagal Pecah Stok')->body('Terjadi kesalahan: ' . $e->getMessage())->danger()->send();
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
                                ]), // <-- Mendorong grup ini ke kanan
                            ]),
                    ]),
            ]);
    }
}
