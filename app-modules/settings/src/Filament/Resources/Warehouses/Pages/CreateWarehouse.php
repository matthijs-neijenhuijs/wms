<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\Warehouses\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Settings\Filament\Resources\Warehouses\WarehouseResource;

class CreateWarehouse extends CreateRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
