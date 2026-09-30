<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\StockLocations;

use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Products\Filament\Resources\StockLocations\Pages\CreateStockLocation;
use Modules\Products\Filament\Resources\StockLocations\Pages\EditStockLocation;
use Modules\Products\Filament\Resources\StockLocations\Pages\ListStockLocations;
use Modules\Products\Filament\Resources\StockLocations\Pages\ManageStockLocationActivities;
use Modules\Products\Filament\Resources\StockLocations\Schemas\StockLocationForm;
use Modules\Products\Filament\Resources\StockLocations\Tables\StockLocationsTable;
use Modules\Products\Models\StockLocation;
use UnitEnum;

class StockLocationResource extends Resource
{
    protected static ?string $model = StockLocation::class;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            EditStockLocation::class,
            ManageStockLocationActivities::class,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return StockLocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockLocationsTable::configure($table);
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
            'index' => ListStockLocations::route('/'),
            'create' => CreateStockLocation::route('/create'),
            'edit' => EditStockLocation::route('/{record}/edit'),
            'history' => ManageStockLocationActivities::route('/{record}/history'),
        ];
    }
}
