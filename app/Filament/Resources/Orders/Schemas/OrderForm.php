<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('warehouse_id'),

                Select::make('order_statuses_id')
                    ->relationship('orderStatus', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('client_id')
                    ->relationship(
                        name: 'client',
                        modifyQueryUsing: fn ($query) => $query
                            ->join('client_addresses', 'clients.id', '=', 'client_addresses.client_id')
                            ->select('clients.*')
                            ->distinct(),
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->clientDeliveryAddress?->firstname.' '.$record->clientDeliveryAddress?->lastname ?? 'Unknown')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
