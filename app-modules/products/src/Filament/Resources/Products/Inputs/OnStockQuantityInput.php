<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Inputs;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class OnStockQuantityInput
{
    public static function make(): TextInput
    {
        return TextInput::make('on_stock_quantity')
            ->label('Quantity on stock')
            ->numeric()
            ->minValue(0)
            ->default(0)
            ->required()
            ->live()
            ->afterStateHydrated(fn (Set $set, Get $get) => FreeStockUpdater::update($set, $get))
            ->afterStateUpdated(fn (Set $set, Get $get) => FreeStockUpdater::update($set, $get));
    }
}
