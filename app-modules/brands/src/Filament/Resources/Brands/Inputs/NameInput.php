<?php

declare(strict_types=1);

namespace Modules\Brands\Filament\Resources\Brands\Inputs;

use Filament\Forms\Components\TextInput;

class NameInput
{
    public static function make(): TextInput
    {
        return TextInput::make('name')
            ->required();
    }
}
