<?php

namespace App\Filament\Resources\PurchaseOrders;

use App\Filament\Infolists\Components\PurchaseOrderFailedProductsTable;
use App\Filament\Infolists\Components\PurchaseOrderProductsTable;
use App\Filament\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Schemas\PurchaseOrderForm;
use App\Filament\Resources\PurchaseOrders\Tables\PurchaseOrdersTable;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrder as PurchaseOrderModel;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function form(Schema $schema): Schema
    {
        return PurchaseOrderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Purchase order')
                    ->schema([
                        TextEntry::make('generated_custom_purchase_order_id')
                            ->label('ID'),
                        TextEntry::make('products_count')
                            ->label('Products')
                            ->state(fn (PurchaseOrderModel $record): int => $record->products->count()),
                        TextEntry::make('scanned_count')
                            ->label('Scanned')
                            ->state(fn (PurchaseOrderModel $record): string => $record->products->where('scanned', true)->count().' / '.$record->products->count()),
                        TextEntry::make('expected_delivery_date')
                            ->date(),
                        TextEntry::make('processed')
                            ->formatStateUsing(fn (bool|int|null $state): string => $state ? 'Yes' : 'No'),
                        TextEntry::make('completed')
                            ->formatStateUsing(fn (bool|int|null $state): string => $state ? 'Yes' : 'No'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('comments'),
                    ])
                    ->columns(2),
                Section::make('Failed Scans')
                    ->columnSpan('full')
                    ->schema([
                        PurchaseOrderFailedProductsTable::make('failedProducts'),
                    ]),
                Section::make('Products')
                    ->columnSpan('full')
                    ->schema([
                        PurchaseOrderProductsTable::make('products'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return PurchaseOrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'products',
                'failedProducts',
            ])
            ->withCount([
                'products',
                'products as scanned_products_count' => fn ($query) => $query->where('scanned', true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'view' => ViewPurchaseOrder::route('/{record}'),
        ];
    }
}
