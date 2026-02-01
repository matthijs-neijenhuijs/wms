<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\OrderStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->options(OrderStatus::class)
                    ->required()
                    ->default(OrderStatus::Concept),

                TextInput::make('price_with_tax')
                    ->required(),

                Select::make('client_id')
                    ->relationship(name: 'client', titleAttribute: 'id'),
            ]);
    }
}
