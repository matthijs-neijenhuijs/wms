<?php

declare(strict_types=1);

namespace Modules\Brands\Filament\Resources\Brands\Schemas;

use Filament\Schemas\Schema;
use Modules\Brands\Filament\Resources\Brands\Inputs\ActiveToggle;
use Modules\Brands\Filament\Resources\Brands\Inputs\DescriptionInput;
use Modules\Brands\Filament\Resources\Brands\Inputs\NameInput;
use Modules\Brands\Filament\Resources\Brands\Inputs\ReferenceCodeInput;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ActiveToggle::make(),
                ReferenceCodeInput::make(),
                NameInput::make(),
                DescriptionInput::make(),
            ]);
    }
}
