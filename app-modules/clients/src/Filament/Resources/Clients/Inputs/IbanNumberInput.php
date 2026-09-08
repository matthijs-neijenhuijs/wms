<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\Inputs;

use Filament\Forms\Components\TextInput;

class IbanNumberInput
{
    public static function make(): TextInput
    {
        return TextInput::make('iban_number')
            ->label('IBAN number');
    }
}
