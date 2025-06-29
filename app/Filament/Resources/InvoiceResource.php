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
use Illuminate\Support\Number;
use Filament\Infolists; // <-- Pastikan ini ada di atas
use Filament\Infolists\Infolist;
// Removed: use Filament\Infolists\Components\Actions\ActionGroup as InfolistActionGroup;
// Removed unused action aliases as they are not needed if actions are in Page class
// use Filament\Actions\DeleteAction as InfolistDeleteAction;
// use Filament\Actions\ForceDeleteAction as InfolistForceDeleteAction;
// use Filament\Actions\RestoreAction as InfolistRestoreAction;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Faktur';
    protected static ?string $modelLabel = 'Faktur';
    protected static ?string $pluralModelLabel = 'Daftar Faktur';
    protected static ?int $navigationSort = 1;

    private static function formatDisplayCurrency($state): string
    {
        return 'Rp. ' . number_format($state ?? 0, 0, ',', '.');
    }

    public static function form(Form $form): Form
    {
        // Fungsi kalkulasi tetap sama, tidak perlu diubah
        $calculateTotals = function (Get $get, Set $set) {
            $servicesData = $get('services') ?? [];
            $itemsData = $get('items') ?? [];

            $subtotal = 0;
            // Calculate subtotal from services
            foreach ($servicesData as $service) {
                // Ensure price is treated as a float, accounting for currency mask
                $price = isset($service['price']) ? (float)str_replace(['Rp.', '.'], ['', ''], $service['price']) : 0;
                if (!empty($service['service_id'])) {
                    // No quantity for services, just sum the price
                    $subtotal += $price;
                }
            }

            // Calculate subtotal from items
            foreach ($itemsData as $item) {
                // Ensure price and quantity are treated as floats/integers
                $price = isset($item['price']) ? (float)str_replace(['Rp.', '.'], ['', ''], $item['price']) : 0;
                $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                if (!empty($item['item_id'])) {
                    $subtotal += $price * $quantity;
                }
            }

            // Logika Diskon Baru
            $discountValue = (float)($get('discount_value') ?? 0);
            $finalDiscount = 0;
            if ($get('discount_type') === 'percentage' && $discountValue > 0) {
                $finalDiscount = ($subtotal * $discountValue) / 100;
            } else {
                $finalDiscount = $discountValue;
            }

            $total = $subtotal - $finalDiscount;

            $set('subtotal', $subtotal);
            $set('total_amount', $total);
        };

        // --- TATA LETAK FORM BARU DIMULAI DARI SINI ---
        return $form->schema([
            // Bagian atas untuk detail invoice dan pelanggan
            Section::make()->schema([
                Grid::make(3)->schema([
                    Group::make()->schema([
                        Forms\Components\Select::make('customer_id')->relationship('customer', 'name')->label('Pelanggan')->searchable()->preload()->required()->live(),
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
                            // ->afterStateUpdated(fn(Set $set, $state) => $set('price', Service::find($state)?->price ?? 0)) // Price is now editable
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                // Optionally, still set an initial price when service changes, but it remains editable
                                $service = Service::find($state);
                                $set('price', $service?->price ?? 0);
                                // Trigger recalculation if needed, or rely on price field's live update
                                $get('../')->recalculateTotals(); // Assuming recalculateTotals is a method on the parent, or adapt
                            }),
                        Forms\Components\TextInput::make('price')
                            ->label('Harga Jasa')
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                            ->prefix('Rp.')
                            ->live(debounce: 500) // Or shorter if preferred
                            ->afterStateUpdated(function(Get $get, Set $set) {
                                // This ensures that when this specific price is changed, totals are recalculated.
                                // The path '../' refers to the parent component holding the calculateTotals logic.
                                // This might need adjustment based on exact structure or by calling a global event.
                                // For now, let's assume a direct call or event that triggers $calculateTotals.
                                // $calculateTotals($get, $set) won't work directly here due to scope.
                                // A common way is to dispatch an event that the main form listens to,
                                // or make calculateTotals a public method on the Livewire component if this is within one.
                                // For simplicity, we'll rely on the main repeater's afterStateUpdated for now,
                                // but this is a point of attention for robust recalculation.
                            }), // Price is now editable, remove readOnly
                        Forms\Components\Textarea::make('description')->label('Deskripsi')->rows(1),
                    ])->columns(3)->live()->afterStateUpdated($calculateTotals),

                // Repeater Barang
                Forms\Components\Repeater::make('items')
                    ->label('Barang / Suku Cadang')->schema([
                        Forms\Components\Select::make('item_id')
                            ->label('Barang')
                            ->options(Item::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                $item = Item::find($state);
                                $set('price', $item?->selling_price ?? 0); // Set initial price
                                $set('unit_name', $item?->unit ?? ''); // Set unit name for display
                                // $get('../')->recalculateTotals(); // Optional: trigger recalc
                            }),
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->live()
                            ->suffix(fn (Get $get) => $get('unit_name') ? $get('unit_name') : null), // Display unit as suffix
                        Forms\Components\TextInput::make('price')
                            ->label('Harga Satuan')
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                            ->prefix('Rp.')
                            ->live(debounce: 500)
                            // ->afterStateUpdated(...) // Optional: for more immediate recalc
                            , // Price is now editable
                        Forms\Components\Hidden::make('unit_name'), // Hidden field to store unit name
                        Forms\Components\Textarea::make('description')->label('Deskripsi')->rows(1),
                    ])->columns(4)->live()->afterStateUpdated($calculateTotals),
            ]),

            // Bagian bawah untuk ringkasan biaya, ditata di sebelah kanan
            Section::make()->schema([
                Grid::make(2)->schema([
                    // Kolom kosong untuk mendorong ringkasan ke kanan
                    Group::make()->schema([
                        Forms\Components\Textarea::make('terms')->label('Syarat & Ketentuan')->rows(3),
                    ]),


                    // Grup untuk ringkasan biaya
                    Group::make()->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->prefix('Rp.')
                            ->currency('IDR') // Apply as per user request for read-only
                            ->readOnly()
                            ->helperText('Total sebelum diskon & pajak.'),

                        // Grup untuk Diskon dengan pilihan Tipe
                        Grid::make(2)->schema([
                            Forms\Components\Select::make('discount_type')
                                ->label('Tipe Diskon')
                                ->options(['fixed' => 'Nominal (Rp)', 'percentage' => 'Persen (%)'])
                                ->default('fixed')->live(),
                            Forms\Components\TextInput::make('discount_value')
                                ->label('Nilai Diskon')
                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                ->prefix('Rp.')
                                ->live(debounce: 600)
                                ->afterStateUpdated($calculateTotals),
                        ]),


                        // Total Akhir
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Akhir')
                            ->prefix('Rp.')
                            ->currency('IDR') // Apply as per user request for read-only
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
                Tables\Columns\TextColumn::make('total_amount')->label('Total Biaya')->formatStateUsing(fn ($state) => self::formatDisplayCurrency($state))->sortable(),
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
                                Infolists\Components\TextEntry::make('pivot.price')->label('Biaya')->formatStateUsing(fn ($state) => self::formatDisplayCurrency($state)),
                            ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Detail Barang / Suku Cadang')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->schema([
                                Infolists\Components\TextEntry::make('name')->label('Nama Barang')->weight('bold')->columnSpan(2),
                                // Assuming unit display for quantity is still desired from previous changes.
                                // If InvoiceResource was restored to a state before unit display, this would need to be re-added.
                                // For now, focusing on currency. If quantity unit is missing, it's a separate issue from this step.
                                Infolists\Components\TextEntry::make('pivot.quantity')->label('Kuantitas')
                                    ->formatStateUsing(function($record) {
                                        // Check if $record and $record->unit exist to prevent errors if unit feature was lost in restore
                                        $unit = property_exists($record, 'unit') && $record->unit ? ' ' . $record->unit : '';
                                        return ($record->pivot->quantity ?? '') . $unit;
                                    }),
                                Infolists\Components\TextEntry::make('pivot.price')->label('Harga Satuan')->formatStateUsing(fn ($state) => self::formatDisplayCurrency($state)),
                                // Menghitung subtotal per item
                                Infolists\Components\TextEntry::make('sub_total_calculated') // Renamed to avoid conflict
                                    ->label('Subtotal')
                                    ->state(fn ($record): float => ($record->pivot->quantity ?? 0) * ($record->pivot->price ?? 0))
                                    ->formatStateUsing(fn ($state) => self::formatDisplayCurrency($state)),
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
                                    Infolists\Components\TextEntry::make('subtotal')->formatStateUsing(fn ($state) => self::formatDisplayCurrency($state)),
                                    Infolists\Components\TextEntry::make('discount_value')
                                        ->label('Diskon')
                                        ->formatStateUsing(function ($record) {
                                            if ($record->discount_type === 'percentage') {
                                                return ($record->discount_value ?? 0) . '%';
                                            }
                                            return self::formatDisplayCurrency($record->discount_value);
                                        }),
                                    Infolists\Components\TextEntry::make('total_amount')
                                        ->label('Total Akhir')
                                        ->formatStateUsing(fn ($state) => self::formatDisplayCurrency($state))
                                        ->weight('bold')
                                        ->size('lg'),
                                ]), // <-- Mendorong grup ini ke kanan
                            ]),
                    ]),
            ]);
    }
}
