<?php

namespace App\Filament\Widgets;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Inventory Overview';

    protected function getStats(): array
    {
        $inventoryValue = (float) Inventory::query()
            ->join('product_variants', 'product_variants.id', '=', 'inventories.product_variant_id')
            ->selectRaw('COALESCE(SUM(inventories.on_hand * product_variants.unit_cost), 0) as value')
            ->value('value');

        return [
            Stat::make('Active SKUs', number_format(ProductVariant::query()->where('is_active', true)->count()))
                ->description('Variant-level catalog coverage'),
            Stat::make('Units On Hand', number_format((int) Inventory::query()->sum('on_hand')))
                ->description('Current physical stock across all variants'),
            Stat::make('Inventory Value', 'AUD '.number_format($inventoryValue, 2))
                ->description('Estimated value at unit cost'),
            Stat::make('Low Stock Alerts', number_format(Inventory::query()->lowStock()->count()))
                ->description(number_format(PurchaseOrder::query()->where('status', PurchaseOrder::STATUS_ORDERED)->count()).' purchase orders still open'),
        ];
    }
}
