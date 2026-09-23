<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Inputs;

use Filament\Forms\Components\TextInput;

class QuantityInput
{
    public static function make(): TextInput
    {
        return TextInput::make('quantity')
            ->numeric()
            ->minValue(1)
            ->default(1)
            ->required();
    }
}
