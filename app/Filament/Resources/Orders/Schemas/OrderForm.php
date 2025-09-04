<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('price_with_tax')
                    ->required(),


Select::make('client_id')
    ->relationship(name: 'client', titleAttribute: 'id'),


            ]);
    }
}
