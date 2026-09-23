<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Inputs;

use Filament\Forms\Components\TextInput;

class ReferenceCodeInput
{
    public static function make(): TextInput
    {
        return TextInput::make('reference_code')
            ->required();
    }
}
