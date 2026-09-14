<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\Inputs;

use Filament\Forms\Components\TextInput;

class VatNumberInput
{
    public static function make(): TextInput
    {
        return TextInput::make('vat_number')
            ->label('VAT number');
    }
}
