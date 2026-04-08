<?php

namespace App\Filament\Resources\Suppliers;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Suppliers\RelationManagers\PurchaseOrdersRelationManager;
use App\Filament\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\Resources\Suppliers\Pages\ListSuppliers;
use App\Filament\Resources\Suppliers\Pages\ViewSupplier;
use App\Models\Supplier;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SupplierResource extends BaseResource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('supplier_code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('contact_person')
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
                TextInput::make('lead_time_days')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                TextInput::make('payment_terms_days')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                Select::make('status')
                    ->options(Supplier::statusOptions())
                    ->required(),
                Textarea::make('address')
                    ->rows(2)
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('supplier_code'),
                TextEntry::make('name'),
                TextEntry::make('contact_person')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->placeholder('-'),
                TextEntry::make('phone')
                    ->placeholder('-'),
                TextEntry::make('lead_time_days')
                    ->suffix(' days'),
                TextEntry::make('payment_terms_days')
                    ->suffix(' days'),
                TextEntry::make('purchase_order_count')
                    ->label('Purchase Orders')
                    ->state(fn (Supplier $record): string => number_format($record->purchaseOrderCount())),
                TextEntry::make('received_purchase_spend')
                    ->label('Received Spend')
                    ->state(fn (Supplier $record): string => 'AUD '.number_format($record->receivedPurchaseSpend(), 2)),
                TextEntry::make('active_variant_count')
                    ->label('Active Variants')
                    ->state(fn (Supplier $record): string => number_format($record->activeVariantCount())),
                TextEntry::make('latest_purchase_order_date')
                    ->label('Last Order Date')
                    ->state(fn (Supplier $record): string => $record->latestPurchaseOrderDate()?->format('d M Y') ?? '-'),
                TextEntry::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Str::headline($state)),
                TextEntry::make('address')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('supplier_code')
                    ->label('Code')
                    ->badge()
                    ->searchable(),
                TextColumn::make('contact_person')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('lead_time_days')
                    ->label('Lead Time')
                    ->suffix(' days')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Supplier::STATUS_ACTIVE => 'success',
                        Supplier::STATUS_ON_HOLD => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Str::headline($state)),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(Supplier::statusOptions()),
            ])
            ->recordActions([
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
            PurchaseOrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'view' => ViewSupplier::route('/{record}'),
            'edit' => EditSupplier::route('/{record}/edit'),
        ];
    }
}
