<?php

namespace App\Filament\Resources\Picklists\Pages;

use App\Filament\Resources\Picklists\PicklistResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPicklist extends ViewRecord
{
    protected static string $resource = PicklistResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
