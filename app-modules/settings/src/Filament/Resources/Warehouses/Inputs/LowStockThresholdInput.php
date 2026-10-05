<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\Warehouses\Inputs;

use Filament\Forms\Components\TextInput;

class LowStockThresholdInput
{
    public static function make(): TextInput
    {
        return TextInput::make('low_stock_threshold')
            ->label('Low stock threshold')
            ->helperText('Products at or below this free-stock quantity are flagged as low stock on the dashboard.')
            ->numeric()
            ->integer()
            ->minValue(0)
            ->default(5)
            ->required();
    }
}
