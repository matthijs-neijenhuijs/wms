<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\Inputs;

use Filament\Forms\Components\TextInput;

class DebtorNumberInput
{
    public static function make(): TextInput
    {
        return TextInput::make('debtor_number')
            ->label('Debtor number');
    }
}
