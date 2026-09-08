<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Inputs;

use Filament\Forms\Components\TextInput;

class CityInput
{
    public static function make(): TextInput
    {
        return TextInput::make('city')
            ->required()
            ->maxLength(255);
    }
}
