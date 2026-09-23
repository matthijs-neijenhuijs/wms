<?php

declare(strict_types=1);

namespace Modules\Brands\Filament\Resources\Brands\Pages;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Filament\Resources\Pages\ManageRelatedRecords;
use Modules\Brands\Filament\Resources\Brands\BrandResource;

class ManageBrandActivities extends ManageRelatedRecords
{
    protected static string $resource = BrandResource::class;

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
