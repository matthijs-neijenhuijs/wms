<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\StockLocations\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Products\Filament\Resources\StockLocations\StockLocationResource;

class ListStockLocations extends ListRecords
{
    protected static string $resource = StockLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
