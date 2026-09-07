<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\OrderStatuses\Inputs;

use Filament\Forms\Components\Toggle;

class OnHoldToggle
{
    public static function make(): Toggle
    {
        return Toggle::make('on_hold')
            ->label(__('On Hold'))
            ->default(false);
    }
}
