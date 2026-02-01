<?php

namespace App\Filament\Resources\VatRates\Pages;

use App\Filament\Resources\VatRates\VatRateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

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
