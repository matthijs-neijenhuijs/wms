<?php

declare(strict_types=1);

namespace Modules\Brands\Filament\Resources\Brands\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Brands\Filament\Resources\Brands\BrandResource;

class CreateBrand extends CreateRecord
{
    protected static string $resource = BrandResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
