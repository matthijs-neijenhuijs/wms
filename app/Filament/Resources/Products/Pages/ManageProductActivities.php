<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\ManageRelatedRecords;

class ManageProductActivities extends ManageRelatedRecords
{
    protected static string $resource = ProductResource::class;

    protected static string $relationship = 'activities';

    protected static ?string $relatedResource = ActivityLogResource::class;

    protected static ?string $navigationLabel = 'History';

    protected static ?string $breadcrumb = 'History';

    protected static ?string $title = 'History';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return null;
    }
}
