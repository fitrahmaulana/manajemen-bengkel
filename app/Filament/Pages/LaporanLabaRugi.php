<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\LabaRugiStatsOverviewWidget;
use App\Models\InvoiceItem;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Support\Facades\DB;

class LaporanLabaRugi extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationLabel = 'Laba-Rugi';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $title = 'Laporan Laba-Rugi';
    protected static string $view = 'filament.pages.laporan-laba-rugi';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'startDate' => now()->startOfMonth()->format('Y-m-d'),
            'endDate' => now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('startDate')
                    ->label('Tanggal Mulai')
                    ->default(now()->startOfMonth())
                    ->reactive(),
                DatePicker::make('endDate')
                    ->label('Tanggal Selesai')
                    ->default(now()->endOfMonth())
                    ->reactive(),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        $startDate = $this->form->getState()['startDate'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->form->getState()['endDate'] ?? now()->endOfMonth()->format('Y-m-d');

        return $table
            ->query(
                InvoiceItem::query()
                    ->join('items', 'invoice_item.item_id', '=', 'items.id')
                    ->join('invoices', 'invoice_item.invoice_id', '=', 'invoices.id')
                    ->whereBetween('invoices.invoice_date', [$startDate, $endDate])
                    ->select('invoice_item.*', 'items.name as item_name', 'items.purchase_price', 'invoices.invoice_number', 'invoices.invoice_date')
            )
            ->columns([
                TextColumn::make('invoice_date')->label('Tanggal Invoice')->date('d M Y')->sortable(),
                TextColumn::make('invoice_number')->label('Nomor Invoice')->searchable()->sortable(),
                TextColumn::make('item_name')->label('Item')->searchable()->sortable(),
                TextColumn::make('quantity')->label('Jumlah'),
                TextColumn::make('price')->label('Harga Jual')->currency('IDR'),
                TextColumn::make('purchase_price')->label('Harga Beli')->currency('IDR'),
                TextColumn::make('profit')->label('Laba')->currency('IDR')
                    ->getStateUsing(fn ($record) => ($record->price - $record->purchase_price) * $record->quantity),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        $startDate = $this->form->getState()['startDate'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->form->getState()['endDate'] ?? now()->endOfMonth()->format('Y-m-d');

        return [
            WidgetConfiguration::make(LabaRugiStatsOverviewWidget::class, [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]),
        ];
    }
}
