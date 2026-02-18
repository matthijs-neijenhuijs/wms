<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Select::make('currency')
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
                    ->searchable(),
            ]);
    }
}
