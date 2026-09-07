<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\AttributeGroups\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Settings\Filament\Resources\AttributeGroups\AttributeGroupResource;

class CreateAttributeGroup extends CreateRecord
{
    protected static string $resource = AttributeGroupResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
