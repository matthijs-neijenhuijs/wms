<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\ApiKeys\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Settings\Filament\Resources\ApiKeys\ApiKeyResource;

class EditApiKey extends EditRecord
{
    protected static string $resource = ApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
