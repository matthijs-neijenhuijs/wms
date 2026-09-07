<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\OrderStatuses\Inputs;

use Filament\Forms\Components\Toggle;

class CancelledToggle
{
    public static function make(): Toggle
    {
        return Toggle::make('cancelled')
            ->label(__('Cancelled'))
            ->default(false);
    }
}
