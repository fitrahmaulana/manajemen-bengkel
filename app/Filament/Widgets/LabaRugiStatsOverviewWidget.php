<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class LabaRugiStatsOverviewWidget extends BaseWidget
{
    protected $listeners = ['datesChanged'];

    public ?string $startDate = null;
    public ?string $endDate = null;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
    }

    public function datesChanged($startDate, $endDate): void
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    protected function getStats(): array
    {
        $revenue = Invoice::query()
            ->whereBetween('invoice_date', [$this->startDate, $this->endDate])
            ->sum('total_amount');

        $cogs = InvoiceItem::query()
            ->join('items', 'invoice_item.item_id', '=', 'items.id')
            ->join('invoices', 'invoice_item.invoice_id', '=', 'invoices.id')
            ->whereBetween('invoices.invoice_date', [$this->startDate, $this->endDate])
            ->sum(DB::raw('invoice_item.quantity * items.purchase_price'));

        $profit = $revenue - $cogs;

        return [
            Stat::make('Total Pendapatan (Invoice)', 'Rp ' . number_format($revenue, 0, ',', '.'))
                ->description('Total pendapatan berdasarkan invoice')
                ->color('success'),
            Stat::make('Total HPP (COGS)', 'Rp ' . number_format($cogs, 0, ',', '.'))
                ->description('Total harga pokok penjualan')
                ->color('warning'),
            Stat::make('Laba Kotor', 'Rp ' . number_format($profit, 0, ',', '.'))
                ->description('Pendapatan - HPP')
                ->color($profit >= 0 ? 'success' : 'danger'),
        ];
    }
}
