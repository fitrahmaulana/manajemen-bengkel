<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class OmzetStatsOverviewWidget extends BaseWidget
{
    public ?string $startDate = null;
    public ?string $endDate = null;

    protected function getStats(): array
    {
        $query = Payment::query();

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('payment_date', [$this->startDate, $this->endDate]);
        }

        $totalOmzet = $query->sum('amount_paid');

        $data = Trend::model(Payment::class)
            ->between(
                start: $this->startDate ? now()->parse($this->startDate) : now()->startOfMonth(),
                end: $this->endDate ? now()->parse($this->endDate) : now()->endOfMonth(),
            )
            ->perDay()
            ->sum('amount_paid');


        return [
            Stat::make('Total Omzet', 'Rp ' . number_format($totalOmzet, 0, ',', '.'))
                ->description('Total pendapatan dari semua transaksi')
                ->color('success')
                ->chart(
                    $data
                        ->map(fn (TrendValue $value) => $value->aggregate)
                        ->all()
                ),
        ];
    }
}
