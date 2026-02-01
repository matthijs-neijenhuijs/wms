<?php

namespace App\Filament\Resources\Picklists\Pages;

use App\Filament\Resources\Picklists\PicklistResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPicklist extends EditRecord
{
    protected static string $resource = PicklistResource::class;

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
