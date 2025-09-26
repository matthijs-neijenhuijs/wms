<?php

namespace App\Filament\Resources\Picklists\Pages;

use App\Filament\Resources\Picklists\PicklistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPicklists extends ListRecords
{
    protected static string $resource = PicklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
