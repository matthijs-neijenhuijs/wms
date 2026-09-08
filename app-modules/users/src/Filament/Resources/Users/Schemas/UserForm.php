<?php

declare(strict_types=1);

namespace Modules\Users\Filament\Resources\Users\Schemas;

use Filament\Schemas\Schema;
use Modules\Users\Filament\Resources\Users\Inputs\EmailInput;
use Modules\Users\Filament\Resources\Users\Inputs\NameInput;
use Modules\Users\Filament\Resources\Users\Inputs\PasswordInput;
use Modules\Users\Filament\Resources\Users\Inputs\SubdomainSelect;
use Modules\Users\Filament\Resources\Users\Inputs\WarehousesSelect;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                SubdomainSelect::make(),
                NameInput::make(),
                EmailInput::make(),
                PasswordInput::make(),
                WarehousesSelect::make(),
            ]);
    }
}
