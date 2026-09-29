<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\StockLocations\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Products\Filament\Resources\StockLocations\StockLocationResource;

class EditStockLocation extends EditRecord
{
    protected static string $resource = StockLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
