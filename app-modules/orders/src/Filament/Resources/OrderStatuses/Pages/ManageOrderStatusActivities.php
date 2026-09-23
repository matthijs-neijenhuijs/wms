<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\OrderStatuses\Pages;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Filament\Resources\Pages\ManageRelatedRecords;
use Modules\Orders\Filament\Resources\OrderStatuses\OrderStatusResource;

class ManageOrderStatusActivities extends ManageRelatedRecords
{
    protected static string $resource = OrderStatusResource::class;

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
