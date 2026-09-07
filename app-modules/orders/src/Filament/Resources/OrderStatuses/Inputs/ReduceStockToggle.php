<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\OrderStatuses\Inputs;

use Filament\Forms\Components\Toggle;

class ReduceStockToggle
{
    public static function make(): Toggle
    {
        return Toggle::make('reduce_stock')
            ->label(__('Reduce Stock'))
            ->default(false);
    }
}
