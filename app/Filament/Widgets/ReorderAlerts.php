<?php

namespace App\Filament\Widgets;

use App\Models\Inventory;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ReorderAlerts extends TableWidget
{
    protected static ?string $heading = 'Reorder Alerts';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Inventory::query()->with(['productVariant.product', 'productVariant.supplier'])->lowStock()->orderBy('on_hand'))
            ->columns([
                TextColumn::make('productVariant.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('productVariant.product.name')
                    ->label('Product')
                    ->searchable(),
                TextColumn::make('productVariant.supplier.name')
                    ->label('Supplier')
                    ->placeholder('-'),
                TextColumn::make('on_hand')
                    ->label('On Hand'),
                TextColumn::make('reorder_point')
                    ->label('Reorder At'),
                TextColumn::make('shortage')
                    ->label('Shortage')
                    ->state(fn (Inventory $record): int => max($record->reorder_point - $record->on_hand, 0)),
                TextColumn::make('reorder_quantity')
                    ->label('Recommended Order'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([]);
    }
}
