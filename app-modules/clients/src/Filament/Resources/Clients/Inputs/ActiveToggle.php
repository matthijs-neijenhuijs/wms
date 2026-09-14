<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\Inputs;

use Filament\Forms\Components\Toggle;

class ActiveToggle
{
    public static function make(): Toggle
    {
        return Toggle::make('active')
            ->inline(false)
            ->required();
    }
}
