<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\Warehouses\Schemas;

use Filament\Schemas\Schema;
use Modules\Settings\Filament\Resources\Warehouses\Inputs\CompletedPicklistStatusSelect;
use Modules\Settings\Filament\Resources\Warehouses\Inputs\CurrencySelect;
use Modules\Settings\Filament\Resources\Warehouses\Inputs\FloorHeightInput;
use Modules\Settings\Filament\Resources\Warehouses\Inputs\FloorWidthInput;
use Modules\Settings\Filament\Resources\Warehouses\Inputs\NameInput;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                NameInput::make(),
                CurrencySelect::make(),
                FloorWidthInput::make(),
                FloorHeightInput::make(),
                CompletedPicklistStatusSelect::make(),
            ]);
    }
}
