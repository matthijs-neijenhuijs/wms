<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Inputs;

use Filament\Forms\Components\Toggle;

class ActiveToggle
{
    public static function make(): Toggle
    {
        return Toggle::make('active')
            ->inline(false)
            ->required()
            ->columnSpan(2);
    }
}
