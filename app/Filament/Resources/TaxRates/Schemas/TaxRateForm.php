<?php

namespace App\Filament\Resources\TaxRates\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;


class TaxRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                           TextInput::make('name'),
                      TextInput::make('rate')
    ->numeric()
 
            ]);
    }
}
