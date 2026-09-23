<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Inputs;

use Filament\Forms\Components\TextInput;

class ReferenceCodeInput
{
    public static function make(): TextInput
    {
        return TextInput::make('reference_code')
            ->disabled()
            ->dehydrated();
    }
}
