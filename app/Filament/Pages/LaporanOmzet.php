<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\OmzetStatsOverviewWidget;
use App\Models\Payment;
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

class LaporanOmzet extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Pendapatan (Omzet)';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $title = 'Laporan Pendapatan (Omzet)';
    protected static string $view = 'filament.pages.laporan-omzet';

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
                Payment::query()->whereBetween('payment_date', [$startDate, $endDate])
            )
            ->columns([
                TextColumn::make('payment_date')->label('Tanggal Pembayaran')->date('d M Y')->sortable(),
                TextColumn::make('payable.invoice_number')->label('Nomor Invoice')->searchable()->sortable(),
                TextColumn::make('amount_paid')->label('Jumlah Dibayar')->currency('IDR')->sortable(),
                TextColumn::make('payment_method')->label('Metode Pembayaran')->badge()->searchable(),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        $startDate = $this->form->getState()['startDate'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->form->getState()['endDate'] ?? now()->endOfMonth()->format('Y-m-d');

        return [
            WidgetConfiguration::make(OmzetStatsOverviewWidget::class, [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]),
        ];
    }
}
