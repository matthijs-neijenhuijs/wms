<?php

declare(strict_types=1);

namespace Modules\Picklists\Filament\Resources\Picklists\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Picklists\Filament\Resources\Picklists\PicklistResource;

class ListPicklists extends ListRecords
{
    protected static string $resource = PicklistResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
