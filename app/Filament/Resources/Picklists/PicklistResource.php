<?php

namespace App\Filament\Resources\Picklists;

use App\Filament\Resources\Picklists\Pages\CreatePicklist;
use App\Filament\Resources\Picklists\Pages\ListPicklists;
use App\Filament\Resources\Picklists\Pages\ViewPicklist;
use App\Filament\Resources\Picklists\Schemas\PicklistForm;
use App\Filament\Resources\Picklists\Tables\PicklistsTable;
use App\Models\Picklist;
use App\Models\Picklist as PicklistModel;
use BackedEnum;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PicklistResource extends Resource
{
    protected static ?string $model = Picklist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

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
                            ->label('Order ID'),
                        TextEntry::make('order.orderStatus.name')
                            ->label('Order status'),
                        TextEntry::make('order.client.email')
                            ->label('Client email'),
                        TextEntry::make('order.delivery_name'),
                        TextEntry::make('order.delivery_city'),
                        TextEntry::make('order.delivery_country'),
                    ])
                    ->columns(3),
                Section::make('Products')
                    ->schema([
                        RepeatableEntry::make('priority_products')
                            ->label('Priority products (reference starts with 66)')
                            ->state(fn (PicklistModel $record) => $record->products
                                ->filter(fn ($product): bool => str_starts_with((string) ($product->reference_code ?? ''), '66'))
                                ->values()
                                ->all())
                            ->schema([
                                TextEntry::make('product_title')
                                    ->label('Product'),
                                TextEntry::make('ean_code')
                                    ->label('EAN'),
                                TextEntry::make('reference_code')
                                    ->label('Reference'),
                                TextEntry::make('color')
                                    ->label('Color'),
                                TextEntry::make('size')
                                    ->label('Size'),
                                TextEntry::make('scanned')
                                    ->formatStateUsing(fn (bool|int|null $state): string => $state ? 'Yes' : 'No'),
                            ])
                            ->columns(6),
                        RepeatableEntry::make('regular_products')
                            ->label('Other products')
                            ->state(fn (PicklistModel $record) => $record->products
                                ->filter(fn ($product): bool => ! str_starts_with((string) ($product->reference_code ?? ''), '66'))
                                ->values()
                                ->all())
                            ->schema([
                                TextEntry::make('product_title')
                                    ->label('Product'),
                                TextEntry::make('ean_code')
                                    ->label('EAN'),
                                TextEntry::make('reference_code')
                                    ->label('Reference'),
                                TextEntry::make('color')
                                    ->label('Color'),
                                TextEntry::make('size')
                                    ->label('Size'),
                                TextEntry::make('scanned')
                                    ->formatStateUsing(fn (bool|int|null $state): string => $state ? 'Yes' : 'No'),
                            ])
                            ->columns(6),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'order.orderStatus',
                'order.client',
                'products',
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
            'create' => CreatePicklist::route('/create'),
            'view' => ViewPicklist::route('/{record}'),
        ];
    }
}
