<?php

declare(strict_types=1);

namespace Modules\Users\Filament\Resources\Users\Inputs;

use Filament\Forms\Components\TextInput;

class EmailInput
{
    public static function make(): TextInput
    {
        return TextInput::make('email')
            ->email()
            ->required()
            ->maxLength(255)
            ->unique(ignoreRecord: true);
    }
}
