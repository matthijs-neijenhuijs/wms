<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Inputs;

use Filament\Forms\Components\TextInput;

class AddressInput
{
    public static function make(): TextInput
    {
        return TextInput::make('address')
            ->required()
            ->maxLength(255);
    }
}
