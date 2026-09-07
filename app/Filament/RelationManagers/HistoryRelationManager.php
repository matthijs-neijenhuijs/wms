<?php

declare(strict_types=1);

namespace App\Filament\RelationManagers;

use AlizHarb\ActivityLog\RelationManagers\ActivitiesRelationManager;

class HistoryRelationManager extends ActivitiesRelationManager
{
    protected static ?string $title = 'History';
}
