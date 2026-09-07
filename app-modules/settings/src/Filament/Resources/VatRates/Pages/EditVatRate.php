<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\VatRates\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Settings\Filament\Resources\VatRates\VatRateResource;

class EditVatRate extends EditRecord
{
    protected static string $resource = VatRateResource::class;

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
