<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PurchaseOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseOrders';

    protected static ?string $title = 'Purchasing History';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->purchaseOrders()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_number')
            ->columns([
                TextColumn::make('order_number')
                    ->label('PO')
                    ->searchable(),
                TextColumn::make('ordered_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('expected_at')
                    ->date()
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        PurchaseOrder::STATUS_RECEIVED => 'success',
                        PurchaseOrder::STATUS_ORDERED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED => 'warning',
                        PurchaseOrder::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Str::headline($state)),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->state(fn (PurchaseOrder $record): string => 'AUD '.number_format((float) $record->total_amount, 2))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PurchaseOrder::statusOptions()),
            ])
            ->defaultSort('ordered_at', 'desc')
            ->headerActions([])
            ->recordActions([
                Action::make('open_purchase_order')
                    ->label('Open order')
                    ->url(fn (PurchaseOrder $record): string => PurchaseOrderResource::getUrl('view', ['record' => $record])),
            ])
            ->toolbarActions([]);
    }
}