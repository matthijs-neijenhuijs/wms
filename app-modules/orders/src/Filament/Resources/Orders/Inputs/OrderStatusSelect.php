<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\Orders\Inputs;

use Filament\Forms\Components\Select;

class OrderStatusSelect
{
    public static function make(): Select
    {
        return Select::make('order_statuses_id')
            ->relationship('orderStatus', 'name')
            ->required();
    }
}
