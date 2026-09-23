<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Pages;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Filament\Resources\Pages\ManageRelatedRecords;
use Modules\Products\Filament\Resources\Products\ProductResource;

class ManageStockActivities extends ManageRelatedRecords
{
    protected static string $resource = ProductResource::class;

    protected static string $relationship = 'stockActivities';

    protected static ?string $relatedResource = ActivityLogResource::class;

    protected static ?string $navigationLabel = 'Stock History';

    protected static ?string $breadcrumb = 'Stock History';

    protected static ?string $title = 'Stock History';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return null;
    }
}
