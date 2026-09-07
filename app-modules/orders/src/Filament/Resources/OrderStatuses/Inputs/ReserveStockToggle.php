<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\OrderStatuses\Inputs;

use Filament\Forms\Components\Toggle;

class ReserveStockToggle
{
    public static function make(): Toggle
    {
        return Toggle::make('reserve_stock')
            ->label(__('Reserve Stock'))
            ->default(false);
    }
}
