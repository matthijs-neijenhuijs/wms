<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\VatRates\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Settings\Filament\Resources\VatRates\VatRateResource;

class ListVatRates extends ListRecords
{
    protected static string $resource = VatRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
