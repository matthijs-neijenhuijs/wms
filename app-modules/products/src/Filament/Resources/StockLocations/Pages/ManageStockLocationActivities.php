<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\StockLocations\Pages;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Filament\Resources\Pages\ManageRelatedRecords;
use Modules\Products\Filament\Resources\StockLocations\StockLocationResource;

class ManageStockLocationActivities extends ManageRelatedRecords
{
    protected static string $resource = StockLocationResource::class;

    protected static string $relationship = 'activitiesAsSubject';

    protected static ?string $relatedResource = ActivityLogResource::class;

    protected static ?string $navigationLabel = 'History';

    protected static ?string $breadcrumb = 'History';

    protected static ?string $title = 'History';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return null;
    }
}
