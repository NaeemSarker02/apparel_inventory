<?php

namespace App\Filament\Resources\Inventories;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Inventories\Pages\CreateInventory;
use App\Filament\Resources\Inventories\Pages\EditInventory;
use App\Filament\Resources\Inventories\Pages\ListInventories;
use App\Filament\Resources\Inventories\Pages\ViewInventory;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Services\InventoryService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class InventoryResource extends BaseResource
{
    protected static ?string $model = Inventory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'productVariant.sku';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_variant_id')
                    ->relationship(
                        'productVariant',
                        'sku',
                        fn (Builder $query): Builder => $query
                            ->with('product')
                    )
                    ->getOptionLabelFromRecordUsing(fn (Model $record): string => sprintf(
                        '%s - %s',
                        $record->sku,
                        $record->product?->name ?? 'Unknown product'
                    ))
                    ->searchable()
                    ->preload()
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    ->helperText('Selecting a variant with an existing inventory record will update that record.')
                    ->required(),
                TextInput::make('on_hand')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('reserved')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('reorder_point')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('reorder_quantity')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('safety_stock')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('productVariant.sku')
                    ->label('SKU')
                    ->placeholder('-'),
                TextEntry::make('productVariant.product.name')
                    ->label('Product')
                    ->placeholder('-'),
                TextEntry::make('on_hand'),
                TextEntry::make('reserved'),
                TextEntry::make('reorder_point'),
                TextEntry::make('reorder_quantity'),
                TextEntry::make('safety_stock'),
                TextEntry::make('last_restocked_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('last_counted_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('productVariant.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('productVariant.product.name')
                    ->label('Product')
                    ->searchable(),
                TextColumn::make('productVariant.color_name')
                    ->label('Color')
                    ->toggleable(),
                TextColumn::make('productVariant.size')
                    ->label('Size')
                    ->badge(),
                TextColumn::make('on_hand')
                    ->sortable(),
                TextColumn::make('available_to_sell')
                    ->label('Available')
                    ->state(fn (Inventory $record): int => $record->availableToSell()),
                TextColumn::make('reorder_point')
                    ->label('Reorder At'),
                TextColumn::make('stock_status')
                    ->label('Stock')
                    ->state(fn (Inventory $record): string => $record->stockStatus())
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'low' => 'warning',
                        default => 'success',
                    }),
            ])
            ->filters([
                Filter::make('low_stock')
                    ->label('Low stock only')
                    ->query(fn (Builder $query): Builder => $query->lowStock()),
            ])
            ->recordActions([
                Action::make('adjust_stock')
                    ->label('Adjust stock')
                    ->visible(fn (Inventory $record): bool => static::canEdit($record))
                    ->schema([
                        TextInput::make('quantity_change')
                            ->numeric()
                            ->required()
                            ->helperText('Use a positive number for inbound adjustments and a negative number for write-offs.'),
                        Textarea::make('notes')
                            ->rows(3),
                    ])
                    ->action(function (Inventory $record, array $data): void {
                        app(InventoryService::class)->adjustInventory(
                            inventory: $record,
                            quantityChange: (int) $data['quantity_change'],
                            type: StockMovement::TYPE_ADJUSTMENT,
                            actor: Auth::user(),
                            referenceType: 'manual_adjustment',
                            referenceNumber: 'INV-'.$record->id,
                            notes: $data['notes'] ?? null,
                        );

                        Notification::make()
                            ->title('Inventory updated')
                            ->success()
                            ->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canDelete(Model $record): bool
    {
        return (static::currentUser()?->isAdmin() ?? false)
            && (! $record->stockMovements()->exists());
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Inventory::query()->lowStock()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventories::route('/'),
            'create' => CreateInventory::route('/create'),
            'view' => ViewInventory::route('/{record}'),
            'edit' => EditInventory::route('/{record}/edit'),
        ];
    }
}
