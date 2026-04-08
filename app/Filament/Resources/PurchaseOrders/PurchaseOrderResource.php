<?php

namespace App\Filament\Resources\PurchaseOrders;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Models\PurchaseOrder;
use App\Services\InventoryService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PurchaseOrderResource extends BaseResource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_number')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Generated automatically'),
                Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('ordered_by')
                    ->relationship('orderedBy', 'name')
                    ->default(fn (): ?int => Auth::id())
                    ->searchable()
                    ->preload(),
                DatePicker::make('ordered_at')
                    ->default(now())
                    ->required(),
                DatePicker::make('expected_at'),
                Select::make('status')
                    ->options([
                        PurchaseOrder::STATUS_DRAFT => 'Draft',
                        PurchaseOrder::STATUS_ORDERED => 'Ordered',
                        PurchaseOrder::STATUS_CANCELLED => 'Cancelled',
                    ])
                    ->required(),
                TextInput::make('shipping_cost')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('AUD')
                    ->default(0),
                Repeater::make('items')
                    ->relationship()
                    ->defaultItems(1)
                    ->reorderable(false)
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('product_variant_id')
                            ->relationship('productVariant', 'sku')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('ordered_quantity')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        TextInput::make('received_quantity')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                        TextInput::make('unit_cost')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('AUD')
                            ->required(),
                    ]),
                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('order_number'),
                TextEntry::make('supplier.name')
                    ->label('Supplier')
                    ->placeholder('-'),
                TextEntry::make('orderedBy.name')
                    ->label('Ordered By')
                    ->placeholder('-'),
                TextEntry::make('ordered_at')
                    ->date(),
                TextEntry::make('expected_at')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Str::headline($state)),
                TextEntry::make('total_amount')
                    ->money('AUD'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('PO')
                    ->searchable(),
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->sortable()
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
                        PurchaseOrder::STATUS_ORDERED => 'warning',
                        PurchaseOrder::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Str::headline($state)),
                TextColumn::make('total_amount')
                    ->money('AUD')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PurchaseOrder::statusOptions()),
                SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->label('Supplier'),
            ])
            ->recordActions([
                Action::make('receive_order')
                    ->label('Receive')
                    ->visible(fn (PurchaseOrder $record): bool => static::canEdit($record) && $record->canReceive())
                    ->requiresConfirmation()
                    ->action(function (PurchaseOrder $record): void {
                        app(InventoryService::class)->receivePurchaseOrder($record, Auth::user());

                        Notification::make()
                            ->title('Purchase order received')
                            ->success()
                            ->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = PurchaseOrder::query()
            ->whereIn('status', [PurchaseOrder::STATUS_ORDERED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'view' => ViewPurchaseOrder::route('/{record}'),
            'edit' => EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
