<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\Warehouses\Schemas;

use Filament\Schemas\Schema;
use Modules\Settings\Filament\Resources\Warehouses\Inputs\CompletedPicklistStatusSelect;
use Modules\Settings\Filament\Resources\Warehouses\Inputs\CurrencySelect;
use Modules\Settings\Filament\Resources\Warehouses\Inputs\NameInput;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                NameInput::make(),
                CurrencySelect::make(),
                CompletedPicklistStatusSelect::make(),
            ]);
    }
}
