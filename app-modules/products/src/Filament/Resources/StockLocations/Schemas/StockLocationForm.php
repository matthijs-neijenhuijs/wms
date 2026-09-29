<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\StockLocations\Schemas;

use Filament\Schemas\Schema;
use Modules\Products\Filament\Resources\StockLocations\Inputs\NameInput;
use Modules\Products\Filament\Resources\StockLocations\Inputs\ParentSelect;

class StockLocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ParentSelect::make(),
                NameInput::make(),
            ]);
    }
}
