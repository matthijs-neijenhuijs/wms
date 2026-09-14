<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Inputs;

use Filament\Forms\Components\Select;

class GenderSelect
{
    public static function make(): Select
    {
        return Select::make('gender')
            ->options([
                'male' => 'Male',
                'female' => 'Female',
            ])
            ->required();
    }
}
