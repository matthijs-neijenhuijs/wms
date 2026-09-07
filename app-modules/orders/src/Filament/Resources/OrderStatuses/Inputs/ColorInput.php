<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\OrderStatuses\Inputs;

use Filament\Forms\Components\ColorPicker;

class ColorInput
{
    public static function make(): ColorPicker
    {
        return ColorPicker::make('color')
            ->required();
    }
}
