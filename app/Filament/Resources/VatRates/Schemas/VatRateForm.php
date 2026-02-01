<?php

namespace App\Filament\Resources\VatRates\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VatRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                TextInput::make('rate')
                    ->numeric(),

            ]);
    }
}
