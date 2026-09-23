<?php

declare(strict_types=1);

namespace Modules\Picklists\Filament\Resources\Picklists\Pages;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Filament\Resources\Pages\ManageRelatedRecords;
use Modules\Picklists\Filament\Resources\Picklists\PicklistResource;

class ManagePicklistActivities extends ManageRelatedRecords
{
    protected static string $resource = PicklistResource::class;

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
