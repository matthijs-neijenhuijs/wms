<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\StockLocations\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Products\Filament\Resources\StockLocations\StockLocationResource;

class CreateStockLocation extends CreateRecord
{
    protected static string $resource = StockLocationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
