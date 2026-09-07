<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\OrderStatuses\Inputs;

use Filament\Forms\Components\Toggle;

class DeliveredToggle
{
    public static function make(): Toggle
    {
        return Toggle::make('delivered')
            ->label(__('Delivered'))
            ->default(false);
    }
}
