<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\Warehouses\Inputs;

use Filament\Forms\Components\Select;

class CurrencySelect
{
    public static function make(): Select
    {
        return Select::make('currency')
            ->options([
                'EUR' => 'Euro (€)',
                'USD' => 'US Dollar ($)',
                'GBP' => 'British Pound (£)',
                'JPY' => 'Japanese Yen (¥)',
                'CHF' => 'Swiss Franc (CHF)',
                'CAD' => 'Canadian Dollar (C$)',
                'AUD' => 'Australian Dollar (A$)',
                'CNY' => 'Chinese Yuan (¥)',
            ])
            ->required()
            ->default('EUR')
            ->searchable();
    }
}
