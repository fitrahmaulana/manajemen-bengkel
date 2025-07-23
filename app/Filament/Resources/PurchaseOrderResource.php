<?php

namespace App\Filament\Resources;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Forms\Components\CustomTableRepeater;
use App\Models\Item;
use App\Models\PurchaseOrder;
use Awcodes\TableRepeater\Header;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Pesanan Pembelian';

    protected static ?string $modelLabel = 'Pesanan Pembelian';

    protected static ?string $pluralModelLabel = 'Daftar Pesanan Pembelian';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(3)->schema([
                    Group::make()->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->label('Supplier')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
                    Group::make()->schema([
                        Forms\Components\TextInput::make('po_number')->label('Nomor PO')->default('PO-'.date('Ymd-His'))->required(),
                        Forms\Components\Select::make('status')
                            ->options(PurchaseOrderStatus::class)
                            ->default(PurchaseOrderStatus::DRAFT)
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation !== 'create'),
                    ]),
                    Group::make()->schema([
                        Forms\Components\DatePicker::make('order_date')->label('Tanggal PO')->default(now())->required(),
                    ]),
                ]),
            ]),

            Section::make('Detail Barang')->schema([
                CustomTableRepeater::make('purchaseOrderItems')
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
                            Forms\Components\Select::make('item_id')
                                ->label('Barang')
                                ->hiddenLabel()
                                ->relationship('item', 'name')
                                ->getOptionLabelFromRecordUsing(fn (Model $record) => $record->display_name)
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                    if ($state) {
                                        $item = Item::find($state);
                                        if ($item) {
                                            $set('price', $item->purchase_price);
                                            $set('unit_name', $item->unit);
                                        }
                                    }
                                }),
                            Forms\Components\Textarea::make('description')->hiddenLabel()->placeholder('Masukkan deskripsi barang')->rows(1),
                        ]),
                        Forms\Components\TextInput::make('quantity')
                            ->label(fn (Get $get) => 'Kuantitas'.($get('unit_name') ? ' ('.$get('unit_name').')' : ''))
                            ->numeric()
                            ->default(1.0)
                            ->required()
                            ->live()
                            ->suffix(fn (Get $get) => $get('unit_name') ? ' '.$get('unit_name') : ''),
                        Forms\Components\TextInput::make('price')
                            ->label('Harga Satuan')
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                            ->prefix('Rp. ')
                            ->live()
                            ->required(),
                        Forms\Components\Hidden::make('unit_name')->dehydrated(false),
                        Forms\Components\Placeholder::make('subtotal')
                            ->hiddenLabel()
                            ->dehydrated(false)
                            ->extraAttributes(['class' => 'text-left md:text-center'])
                            ->content(function (Get $get) {
                                $quantity = (float) ($get('quantity') ?? 0);
                                $price = self::parseCurrencyValue($get('price') ?? '0');
                                $total = $quantity * $price;

                                return self::formatCurrency($total);
                            }),
                    ])
                    ->columns(4),
            ]),

            Section::make()->schema([
                Grid::make(2)->schema([
                    Group::make()->schema([
                        Forms\Components\Textarea::make('notes')->label('Catatan')->rows(3),
                    ]),

                    Group::make()->schema([
                        Forms\Components\Placeholder::make('subtotal')
                            ->label('Subtotal')
                            ->extraAttributes(['class' => 'font-bold text-xl text-white'])
                            ->content(function (Get $get) {
                                $itemsTotal = collect($get('purchaseOrderItems'))->sum(function ($item) {
                                    $quantity = (float) ($item['quantity'] ?? 0.0);
                                    $price = self::parseCurrencyValue($item['price'] ?? '0');

                                    return $quantity * $price;
                                });

                                return self::formatCurrency($itemsTotal);
                            })
                            ->helperText('Total sebelum diskon & pajak.'),

                        Grid::make(2)->schema([
                            Forms\Components\Select::make('discount_type')
                                ->label('Tipe Diskon')
                                ->options(['fixed' => 'Nominal (Rp)', 'percentage' => 'Persen (%)'])
                                ->default('fixed')
                                ->live(),
                            Forms\Components\TextInput::make('discount_value')
                                ->label('Nilai Diskon')
                                ->default(0)
                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                ->prefix(fn (Get $get) => $get('discount_type') === 'fixed' ? 'Rp. ' : '% ')
                                ->live(),
                        ]),

                        Forms\Components\Placeholder::make('total_amount')
                            ->label('Total Akhir')
                            ->extraAttributes(['class' => 'font-bold text-xl text-green'])
                            ->Content(function (Get $get) {
                                $itemsTotal = collect($get('purchaseOrderItems'))->sum(function ($item) {
                                    $quantity = (float) ($item['quantity'] ?? 0);
                                    $price = self::parseCurrencyValue($item['price'] ?? '0');

                                    return $quantity * $price;
                                });

                                $discountType = $get('discount_type') ?? 'fixed';
                                $discountValue = self::parseCurrencyValue($get('discount_value') ?? '0');

                                $discountAmount = 0;
                                if ($discountType === 'percentage') {
                                    $discountAmount = ($itemsTotal * $discountValue) / 100;
                                } else {
                                    $discountAmount = $discountValue;
                                }

                                $totalAmount = $itemsTotal - $discountAmount;

                                return self::formatCurrency($totalAmount);
                            }),
                    ]),
                ]),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')->label('No. PO')->searchable(),
                Tables\Columns\TextColumn::make('supplier.name')->label('Supplier')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Status Pembayaran')
                    ->badge(),
                Tables\Columns\TextColumn::make('total_amount')->label('Total Biaya')->currency('IDR')->sortable(),
                Tables\Columns\TextColumn::make('order_date')->label('Tanggal PO')->date('d M Y')->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Action::make('complete')
                        ->label('Complete')
                        ->action(function (PurchaseOrder $record) {
                            if ($record->status === PurchaseOrderStatus::COMPLETED) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Purchase order is already completed.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            foreach ($record->purchaseOrderItems as $item) {
                                $item->item->stock += $item->quantity;
                                $item->item->save();
                            }

                            $record->status = PurchaseOrderStatus::COMPLETED;
                            $record->save();

                            Notification::make()
                                ->title('Success')
                                ->body('Purchase order has been completed and stock has been updated.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->visible(fn (PurchaseOrder $record) => $record->status === PurchaseOrderStatus::DRAFT),
                    Action::make('revert')
                        ->label('Kembalikan ke Draft')
                        ->action(function (PurchaseOrder $record) {
                            if ($record->status !== PurchaseOrderStatus::COMPLETED) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Purchase order must be completed before reverting.')
                                    ->danger()
                                    ->send();

                                return;
                            }
                            foreach ($record->purchaseOrderItems as $item) {
                                $item->item->decrement('stock', $item->quantity);
                            }

                            $record->status = PurchaseOrderStatus::DRAFT;
                            $record->save();

                            Notification::make()->title('Sukses')->body('Pesanan dikembalikan ke draft dan stok telah disesuaikan.')->success()->send();
                        })
                        ->requiresConfirmation()
                        ->color('warning')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->visible(fn (PurchaseOrder $record) => $record->status === PurchaseOrderStatus::COMPLETED),
                ]),
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
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
        ];
    }

    public static function parseCurrencyValue($value): float
    {
        return (float) preg_replace('/[^0-9,]/', '', $value);
    }

    public static function formatCurrency($value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }

    public static function calculateTotals(array $data): array
    {
        // Hitung Subtotal dari semua item
        $subtotal = collect($data['purchaseOrderItems'] ?? [])->sum(function ($item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            // Gunakan metode parseCurrencyValue dari resource ini
            $price = self::parseCurrencyValue($item['price'] ?? '0');

            return $quantity * $price;
        });

        // Ambil tipe dan nilai diskon
        $discountType = $data['discount_type'] ?? 'fixed';
        $discountValue = self::parseCurrencyValue($data['discount_value'] ?? '0');

        // Hitung jumlah diskon
        $discountAmount = 0;
        if ($discountType === 'percentage') {
            $discountAmount = ($subtotal * $discountValue) / 100;
        } else {
            $discountAmount = $discountValue;
        }

        // Hitung Total Akhir
        $totalAmount = $subtotal - $discountAmount;

        return [
            'subtotal' => $subtotal,
            'total_amount' => $totalAmount,
        ];
    }
}
