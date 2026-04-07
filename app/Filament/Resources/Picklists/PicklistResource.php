<?php

namespace App\Filament\Resources\Picklists;

use App\Filament\Infolists\Components\FailedProductsTable;
use App\Filament\Infolists\Components\ProductsTable;
use App\Filament\Resources\Picklists\Pages\ListPicklists;
use App\Filament\Resources\Picklists\Pages\ViewPicklist;
use App\Filament\Resources\Picklists\Schemas\PicklistForm;
use App\Filament\Resources\Picklists\Tables\PicklistsTable;
use App\Models\Picklist;
use App\Models\Picklist as PicklistModel;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PicklistResource extends Resource
{
    protected static ?string $model = Picklist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return PicklistForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PicklistsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Picklist')
                    ->schema([
                        TextEntry::make('generated_custom_picklist_id')
                            ->label('ID'),
                        TextEntry::make('products_count')
                            ->label('Products')
                            ->state(fn (PicklistModel $record): int => $record->products->count()),
                        TextEntry::make('scanned_count')
                            ->label('Scanned')
                            ->state(fn (PicklistModel $record): string => $record->products->where('scanned', true)->count().' / '.$record->products->count()),
                        TextEntry::make('completed')
                            ->formatStateUsing(fn (bool|int|null $state): string => $state ? 'Yes' : 'No'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('comments'),
                    ])
                    ->columns(2),
                Section::make('Order Summary')
                    ->schema([
                        TextEntry::make('order.generated_custom_order_id')
                            ->label('Order'),
                        TextEntry::make('order.orderStatus.name')
                            ->label('Order status'),
                        TextEntry::make('order.client.email')
                            ->label('Client email'),
                        TextEntry::make('order.delivery_name'),
                        TextEntry::make('order.delivery_city'),
                        TextEntry::make('order.delivery_country'),
                    ])
                    ->columns(3),
                Section::make('Failed Scans')
                    ->columnSpan('full')
                    ->schema([
                        FailedProductsTable::make('failedProducts'),
                    ]),
                Section::make('Products')
                    ->columnSpan('full')
                    ->schema([
                        ProductsTable::make('products'),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'order.orderStatus',
                'order.client',
                'products',
                'failedProducts',
            ])
            ->withCount([
                'products',
                'products as scanned_products_count' => fn ($query) => $query->where('scanned', true),
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
            'index' => ListPicklists::route('/'),
            'view' => ViewPicklist::route('/{record}'),
        ];
    }
}
