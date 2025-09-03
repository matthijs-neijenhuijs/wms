<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Schemas\Schema;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                 Toggle::make('active')->inline(false)
                    ->required()->columnSpan(2),
                TextInput::make('email')->columnSpan(2)
                    ->required(),
                Select::make('bill_client_address_id')->label('Bill address')
                    ->relationship(name: 'clientBillAddress', titleAttribute: 'firstname'),
                Select::make('delivery_client_address_id')->label(label: 'Delivery address')
                    ->relationship(name: 'clientDeliveryAddress', titleAttribute: 'firstname'),


            ]);
    }
}
