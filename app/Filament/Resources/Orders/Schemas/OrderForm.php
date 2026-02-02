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


                Select::make('client_id')
                    ->relationship(
                        name: 'client',
                        modifyQueryUsing: fn ($query) => $query
                            ->join('client_addresses', 'clients.id', '=', 'client_addresses.client_id')
                            ->select('clients.*')
                            ->distinct(),
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->clientDeliveryAddress?->firstname . ' ' . $record->clientDeliveryAddress?->lastname ?? 'Unknown')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
