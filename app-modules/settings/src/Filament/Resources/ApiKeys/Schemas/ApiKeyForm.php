<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\ApiKeys\Schemas;

use Filament\Schemas\Schema;
use Modules\Settings\Filament\Resources\ApiKeys\Inputs\AllowedIpsInput;
use Modules\Settings\Filament\Resources\ApiKeys\Inputs\ExpiresAtInput;
use Modules\Settings\Filament\Resources\ApiKeys\Inputs\IsActiveToggle;
use Modules\Settings\Filament\Resources\ApiKeys\Inputs\NameInput;

class ApiKeyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                NameInput::make(),
                IsActiveToggle::make(),
                AllowedIpsInput::make(),
                ExpiresAtInput::make(),
            ]);
    }
}
