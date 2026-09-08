<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Inputs;

use Filament\Forms\Components\TextInput;

class TelephoneNumberInput
{
    public static function make(): TextInput
    {
        return TextInput::make('telephone_number')
            ->label('Telephone number')
            ->maxLength(50);
    }
}
