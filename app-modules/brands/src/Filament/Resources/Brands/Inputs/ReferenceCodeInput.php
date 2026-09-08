<?php

declare(strict_types=1);

namespace Modules\Brands\Filament\Resources\Brands\Inputs;

use Filament\Forms\Components\TextInput;

class ReferenceCodeInput
{
    public static function make(): TextInput
    {
        return TextInput::make('reference_code')
            ->required();
    }
}
