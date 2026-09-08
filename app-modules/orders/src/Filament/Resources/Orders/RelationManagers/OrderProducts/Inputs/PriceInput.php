<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Inputs;

use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;

class PriceInput
{
    public static function make(RelationManager $relationManager): TextInput
    {
        return TextInput::make('price')
            ->numeric()
            ->prefix(fn () => match ($relationManager->getOwnerRecord()->warehouse->currency ?? 'EUR') {
                'USD' => '$',
                'GBP' => '£',
                'JPY' => '¥',
                'CHF' => 'CHF',
                'CAD' => 'C$',
                'AUD' => 'A$',
                'CNY' => '¥',
                default => '€',
            })
            ->disabled()
            ->dehydrated();
    }
}
