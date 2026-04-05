<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ManageRelatedRecords;

class ManageOrderActivities extends ManageRelatedRecords
{
    protected static string $resource = OrderResource::class;

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
