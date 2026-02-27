<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Client;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_statuses_id')
                    ->relationship('orderStatus', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('client_id')
                    ->relationship(
                        name: 'client',
                        titleAttribute: 'email',
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->with('clientDeliveryAddress')
                            ->orderBy('company')
                            ->orderBy('email'),
                    )
                    ->getOptionLabelFromRecordUsing(fn (Client $record): string => $record->company
                        ?: ($record->clientDeliveryAddress?->name ?: $record->email ?: "Client #{$record->id}"))
                    ->searchable(['company', 'email'])
                    ->searchable()
                    ->preload(),
            ]);
    }
}
