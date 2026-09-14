<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\Inputs;

use Filament\Forms\Components\TextInput;

class CocNumberInput
{
    public static function make(): TextInput
    {
        return TextInput::make('coc_number')
            ->label('CoC number');
    }
}
