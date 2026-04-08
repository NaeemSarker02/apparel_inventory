<?php

namespace App\Filament\Resources\SalesOrders;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\SalesOrders\Pages\CreateSalesOrder;
use App\Filament\Resources\SalesOrders\Pages\EditSalesOrder;
use App\Filament\Resources\SalesOrders\Pages\ListSalesOrders;
use App\Filament\Resources\SalesOrders\Pages\ViewSalesOrder;
use App\Models\SalesOrder;
use App\Services\InventoryService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
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

class SalesOrderResource extends BaseResource
{
    protected static ?string $model = SalesOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_number')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Generated automatically'),
                Select::make('sold_by')
                    ->relationship('soldBy', 'name')
                    ->default(fn (): ?int => Auth::id())
                    ->searchable()
                    ->preload(),
                DateTimePicker::make('sold_at')
                    ->default(now())
                    ->required(),
                Select::make('sales_channel')
                    ->options(SalesOrder::channelOptions())
                    ->required(),
                Select::make('status')
                    ->options([
                        SalesOrder::STATUS_DRAFT => 'Draft',
                        SalesOrder::STATUS_CANCELLED => 'Cancelled',
                    ])
                    ->required(),
                TextInput::make('discount_amount')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('AUD')
                    ->default(0),
                Repeater::make('items')
                    ->relationship()
                    ->defaultItems(1)
                    ->reorderable(false)
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('product_variant_id')
                            ->relationship('productVariant', 'sku')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        TextInput::make('unit_price')
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
                TextEntry::make('soldBy.name')
                    ->label('Handled By')
                    ->placeholder('-'),
                TextEntry::make('sold_at')
                    ->dateTime(),
                TextEntry::make('sales_channel')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Str::headline($state)),
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
                    ->label('Sales Order')
                    ->searchable(),
                TextColumn::make('sold_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('sales_channel')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Str::headline($state)),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        SalesOrder::STATUS_COMPLETED => 'success',
                        SalesOrder::STATUS_CANCELLED => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => Str::headline($state)),
                TextColumn::make('discount_amount')
                    ->money('AUD'),
                TextColumn::make('total_amount')
                    ->money('AUD')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SalesOrder::statusOptions()),
                SelectFilter::make('sales_channel')
                    ->label('Channel')
                    ->options(SalesOrder::channelOptions()),
            ])
            ->recordActions([
                Action::make('complete_sale')
                    ->label('Complete sale')
                    ->visible(fn (SalesOrder $record): bool => static::canEdit($record) && $record->status === SalesOrder::STATUS_DRAFT)
                    ->requiresConfirmation()
                    ->action(function (SalesOrder $record): void {
                        try {
                            app(InventoryService::class)->completeSalesOrder($record, Auth::user());

                            Notification::make()
                                ->title('Sale completed')
                                ->success()
                                ->send();
                        } catch (\DomainException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
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

    public static function getPages(): array
    {
        return [
            'index' => ListSalesOrders::route('/'),
            'create' => CreateSalesOrder::route('/create'),
            'view' => ViewSalesOrder::route('/{record}'),
            'edit' => EditSalesOrder::route('/{record}/edit'),
        ];
    }
}
