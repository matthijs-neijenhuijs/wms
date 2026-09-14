<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Inputs;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class FreeStockUpdater
{
    public static function update(Set $set, Get $get): void
    {
        $freeOnStock = max(
            0,
            (int) $get('on_stock_quantity')
                - (int) $get('reserved_quantity')
                - (int) $get('reserved_on_picklists')
        );

        $set('free_on_stock_quantity', $freeOnStock);
    }
}
