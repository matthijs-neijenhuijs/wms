<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Inputs;

use Filament\Forms\Components\TextInput;

class FreeOnStockQuantityInput
{
    public static function make(): TextInput
    {
        return TextInput::make('free_on_stock_quantity')
            ->label('Free on stock quantity')
            ->numeric()
            ->minValue(0)
            ->default(0)
            ->disabled()
            ->dehydrated()
            ->required();
    }
}
