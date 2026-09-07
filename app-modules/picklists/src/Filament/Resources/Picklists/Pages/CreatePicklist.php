<?php

declare(strict_types=1);

namespace Modules\Picklists\Filament\Resources\Picklists\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Picklists\Filament\Resources\Picklists\PicklistResource;

class CreatePicklist extends CreateRecord
{
    protected static string $resource = PicklistResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
