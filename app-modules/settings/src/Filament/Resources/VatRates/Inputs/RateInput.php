<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\VatRates\Inputs;

use Filament\Forms\Components\TextInput;

class RateInput
{
    public static function make(): TextInput
    {
        return TextInput::make('rate')
            ->numeric();
    }
}
