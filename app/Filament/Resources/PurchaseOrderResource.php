<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Models\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use App\Models\Item;
use Filament\Notifications\Notification;
use App\Forms\Components\CustomTableRepeater;
use Awcodes\TableRepeater\Header;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\HtmlString;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Manajemen Stok';
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
                        Forms\Components\TextInput::make('po_number')->label('Nomor PO')->default('PO-' . date('Ymd-His'))->required(),
                        Forms\Components\Select::make('status')->options(['draft' => 'Draft', 'completed' => 'Completed',])->default('draft')->required(),
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
                                ->options(function () {
                                    return Item::query()
                                        ->with(['product'])
                                        ->get()
                                        ->mapWithKeys(function ($item) {
                                            $productName = $item->product->name;
                                            $variantName = $item->name;

                                            if (empty($variantName)) {
                                                $displayName = $productName;
                                            } else {
                                                $displayName = $productName . ' ' . $variantName;
                                            }

                                            $skuInfo = $item->sku ? " (SKU: " . $item->sku . ")" : "";
                                            $stockInfo = " - Stok: {$item->stock} {$item->unit}";

                                            return [$item->id => $displayName . $skuInfo . $stockInfo];
                                        });
                                })
                                ->searchable()
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
                            Forms\Components\Textarea::make('description')->hiddenLabel()->placeholder('Masukkan deskripsi barang')->rows(1)
                        ]),
                        Forms\Components\TextInput::make('quantity')
                            ->label(fn(Get $get) => 'Kuantitas' . ($get('unit_name') ? ' (' . $get('unit_name') . ')' : ''))
                            ->numeric()
                            ->step('0.01')
                            ->default(1.0)
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('price')
                            ->label('Harga Satuan')
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                            ->prefix('Rp. ')
                            ->live()
                            ->required(),
                        Forms\Components\Hidden::make('unit_name'),
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
                            'Total: ' . self::formatCurrency(collect($get('purchaseOrderItems'))->sum(function ($item) {
                                $quantity = (float)($item['quantity'] ?? 0.0);
                                $price = self::parseCurrencyValue($item['price'] ?? '0');
                                return $quantity * $price;
                            }))
                        )
                    )
                    ->columns(4)
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
                                    $quantity = (float)($item['quantity'] ?? 0.0);
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
                                ->prefix(fn(Get $get) => $get('discount_type') === 'fixed' ? 'Rp. ' : '% ')
                                ->live(),
                        ]),

                        Forms\Components\Placeholder::make('total_amount')
                            ->label('Total Akhir')
                            ->extraAttributes(['class' => 'font-bold text-xl text-green'])
                            ->Content(function (Get $get) {
                                $itemsTotal = collect($get('purchaseOrderItems'))->sum(function ($item) {
                                    $quantity = (float)($item['quantity'] ?? 0);
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
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'completed' => 'Selesai',
                    })->badge()->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'completed' => 'success',
                    })->searchable(),
                Tables\Columns\TextColumn::make('total_price')->label('Total Biaya')->currency('IDR')->sortable(),
                Tables\Columns\TextColumn::make('order_date')->label('Tanggal PO')->date('d M Y')->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('complete')
                    ->label('Complete')
                    ->action(function (PurchaseOrder $record) {
                        if ($record->status === 'completed') {
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

                        $record->status = 'completed';
                        $record->save();

                        Notification::make()
                            ->title('Success')
                            ->body('Purchase order has been completed and stock has been updated.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check-circle'),
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }

    public static function parseCurrencyValue($value): float
    {
        return (float) preg_replace('/[^0-9,]/', '', $value);
    }

    public static function formatCurrency($value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }
}
