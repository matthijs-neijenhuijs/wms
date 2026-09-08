<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Inputs;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class ReservedOnPicklistsInput
{
    public static function make(): TextInput
    {
        return TextInput::make('reserved_on_picklists')
            ->label('Reserved on picklists')
            ->numeric()
            ->minValue(0)
            ->default(0)
            ->required()
            ->live()
            ->afterStateUpdated(fn (Set $set, Get $get) => FreeStockUpdater::update($set, $get));
    }
}
