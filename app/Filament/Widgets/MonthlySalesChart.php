<?php

namespace App\Filament\Widgets;

use App\Models\SalesOrder;
use Illuminate\Support\Carbon;
use Filament\Widgets\ChartWidget;

class MonthlySalesChart extends ChartWidget
{
    protected ?string $heading = 'Monthly Sales Trend';

    protected function getData(): array
    {
        $months = collect(range(5, 0))
            ->mapWithKeys(fn (int $offset): array => [
                now()->subMonths($offset)->format('Y-m') => 0,
            ]);

        $totals = SalesOrder::query()
            ->where('status', SalesOrder::STATUS_COMPLETED)
            ->where('sold_at', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw("strftime('%Y-%m', sold_at) as sale_month")
            ->selectRaw('SUM(total_amount) as revenue')
            ->groupBy('sale_month')
            ->orderBy('sale_month')
            ->pluck('revenue', 'sale_month');

        $series = $months->merge($totals)->sortKeys();

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (AUD)',
                    'data' => $series->values()->map(fn ($value): float => round((float) $value, 2))->all(),
                    'borderColor' => '#b45309',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.18)',
                    'fill' => true,
                ],
            ],
            'labels' => $series->keys()->map(fn (string $month): string => Carbon::createFromFormat('Y-m', $month)->format('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
