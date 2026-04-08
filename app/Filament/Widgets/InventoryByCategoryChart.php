<?php

namespace App\Filament\Widgets;

use App\Models\Inventory;
use Filament\Widgets\ChartWidget;

class InventoryByCategoryChart extends ChartWidget
{
    protected ?string $heading = 'Inventory By Category';

    protected function getData(): array
    {
        $dataset = Inventory::query()
            ->join('product_variants', 'product_variants.id', '=', 'inventories.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('categories.name as category_name')
            ->selectRaw('SUM(inventories.on_hand) as total_units')
            ->groupBy('categories.name')
            ->orderByDesc('total_units')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Units on hand',
                    'data' => $dataset->pluck('total_units')->map(fn ($value): int => (int) $value)->all(),
                    'backgroundColor' => ['#1d4ed8', '#0f766e', '#b45309', '#be123c', '#7c3aed', '#4d7c0f'],
                ],
            ],
            'labels' => $dataset->pluck('category_name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
