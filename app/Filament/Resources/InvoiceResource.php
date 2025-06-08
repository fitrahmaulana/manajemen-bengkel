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
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;

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
            $serviceIds = array_column($servicesData, 'service_id');
            $itemIds = array_column($itemsData, 'item_id');
            $freshServicePrices = Service::find($serviceIds)->pluck('price', 'id');
            $freshItemPrices = Item::find($itemIds)->pluck('selling_price', 'id');

            $subtotal = 0;
            foreach ($servicesData as $service) {
                if (!empty($service['service_id'])) {
                    $subtotal += $freshServicePrices[$service['service_id']] ?? 0;
                }
            }
            foreach ($itemsData as $item) {
                if (!empty($item['item_id'])) {
                    $price = $freshItemPrices[$item['item_id']] ?? 0;
                    $quantity = $item['quantity'] ?? 1;
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
                    ->relationship('invoice_service')->label('Jasa / Layanan')->schema([
                        Forms\Components\Select::make('service_id')->label('Jasa')->options(Service::all()->pluck('name', 'id'))->searchable()->required()->live()->afterStateUpdated(fn(Set $set, $state) => $set('price', Service::find($state)?->price ?? 0)),
                        Forms\Components\TextInput::make('price')->label('Harga Jasa')->numeric()->prefix('Rp')->readOnly(),
                        Forms\Components\Textarea::make('description')->label('Deskripsi')->rows(1),
                    ])->columns(3)->live()->afterStateUpdated($calculateTotals),

                // Repeater Barang
                Forms\Components\Repeater::make('items')
                    ->relationship('invoice_item')->label('Barang / Suku Cadang')->schema([
                        Forms\Components\Select::make('item_id')->label('Barang')->options(Item::all()->pluck('name', 'id'))->searchable()->required()->live()->afterStateUpdated(fn(Set $set, $state) => $set('price', Item::find($state)?->selling_price ?? 0)),
                        Forms\Components\TextInput::make('quantity')->numeric()->default(1)->required()->live(),
                        Forms\Components\TextInput::make('price')->label('Harga Satuan')->numeric()->prefix('Rp')->readOnly(),
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
                        Forms\Components\TextInput::make('subtotal')->label('Subtotal')->numeric()->prefix('Rp')->readOnly()->helperText('Total sebelum diskon & pajak.'),

                        // Grup untuk Diskon dengan pilihan Tipe
                        Grid::make(2)->schema([
                            Forms\Components\Select::make('discount_type')
                                ->label('Tipe Diskon')
                                ->options(['fixed' => 'Nominal (Rp)', 'percentage' => 'Persen (%)'])
                                ->default('fixed')->live(),
                            Forms\Components\TextInput::make('discount_value')
                                ->label('Nilai Diskon')
                                ->numeric()->default(0)->live(debounce: 2000)->afterStateUpdated($calculateTotals),
                        ]),


                        // Total Akhir
                        Forms\Components\TextInput::make('total_amount')->label('Total Akhir')->numeric()->prefix('Rp')->readOnly(),
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
                Tables\Columns\TextColumn::make('total_amount')->label('Total Biaya')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('invoice_date')->label('Tanggal Invoice')->date('d M Y')->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(), // Tambahkan ViewAction untuk melihat detail
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
        ];
    }

    // Ganti juga method ini agar kalkulasi saat penyimpanan juga benar
    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $servicesData = $data['services'] ?? [];
        $itemsData = $data['items'] ?? [];
        $serviceIds = array_column($servicesData, 'service_id');
        $itemIds = array_column($itemsData, 'item_id');
        $freshServicePrices = Service::find($serviceIds)->pluck('price', 'id');
        $freshItemPrices = Item::find($itemIds)->pluck('selling_price', 'id');

        $subtotal = 0;
        foreach ($servicesData as $service) {
            if (!empty($service['service_id'])) {
                $subtotal += $freshServicePrices[$service['service_id']] ?? 0;
            }
        }
        foreach ($itemsData as $item) {
            if (!empty($item['item_id'])) {
                $price = $freshItemPrices[$item['item_id']] ?? 0;
                $quantity = $item['quantity'] ?? 1;
                $subtotal += $price * $quantity;
            }
        }

        // Logika Diskon Baru saat menyimpan
        $discountValue = (float)($data['discount_value'] ?? 0);
        $finalDiscount = 0;
        if ($data['discount_type'] === 'percentage' && $discountValue > 0) {
            $finalDiscount = ($subtotal * $discountValue) / 100;
        } else {
            $finalDiscount = $discountValue;
        }

        $total = $subtotal - $finalDiscount;

        $data['subtotal'] = $subtotal;
        $data['total_amount'] = $total;
        // Tipe dan nilai diskon sudah tersimpan otomatis dari form

        dd($data);
        return $data;
    }


}
