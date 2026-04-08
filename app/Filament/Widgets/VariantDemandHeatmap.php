<?php

namespace App\Filament\Widgets;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Str;

class VariantDemandHeatmap extends TableWidget
{
    protected static ?string $heading = 'Size / Color Demand Heatmap';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SalesOrderItem::query()
                    ->join('sales_orders', function (JoinClause $join): void {
                        $join->on('sales_orders.id', '=', 'sales_order_items.sales_order_id')
                            ->where('sales_orders.status', '=', SalesOrder::STATUS_COMPLETED);
                    })
                    ->join('product_variants', 'product_variants.id', '=', 'sales_order_items.product_variant_id')
                    ->selectRaw('MIN(sales_order_items.id) as id')
                    ->selectRaw('product_variants.season as season')
                    ->selectRaw('product_variants.size as size')
                    ->selectRaw('product_variants.color_name as color_name')
                    ->selectRaw('SUM(sales_order_items.quantity) as units_sold')
                    ->selectRaw('SUM(sales_order_items.line_total) as revenue')
                    ->groupBy('product_variants.season', 'product_variants.size', 'product_variants.color_name')
                    ->orderByDesc('units_sold')
                    ->limit(12)
            )
            ->columns([
                TextColumn::make('season')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? Str::headline($state) : 'Core'),
                TextColumn::make('size')
                    ->badge(),
                TextColumn::make('color_name')
                    ->label('Color')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('units_sold')
                    ->label('Units Sold')
                    ->sortable(),
                TextColumn::make('demand_band')
                    ->label('Demand Band')
                    ->state(function (object $record): string {
                        $unitsSold = (int) $record->units_sold;

                        return match (true) {
                            $unitsSold >= 18 => 'very high',
                            $unitsSold >= 12 => 'high',
                            $unitsSold >= 8 => 'medium',
                            default => 'emerging',
                        };
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'very high' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        default => 'success',
                    }),
                TextColumn::make('revenue')
                    ->label('Revenue')
                    ->state(fn (object $record): string => 'AUD '.number_format((float) $record->revenue, 2)),
            ])
            ->paginated(false)
            ->defaultSort('units_sold', 'desc')
            ->filters([])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}