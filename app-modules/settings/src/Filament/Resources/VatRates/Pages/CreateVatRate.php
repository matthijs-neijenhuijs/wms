<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\VatRates\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Settings\Filament\Resources\VatRates\VatRateResource;

class CreateVatRate extends CreateRecord
{
    protected static string $resource = VatRateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
