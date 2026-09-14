<?php

declare(strict_types=1);

namespace Modules\Brands\Filament\Resources\Brands\Inputs;

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
