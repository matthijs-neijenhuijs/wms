<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\Warehouses\Inputs;

use Filament\Forms\Components\TextInput;

class FloorWidthInput
{
    public static function make(): TextInput
    {
        return TextInput::make('floor_width')
            ->label('Floor width')
            ->numeric()
            ->step(0.01)
            ->minValue(0.1);
    }
}
