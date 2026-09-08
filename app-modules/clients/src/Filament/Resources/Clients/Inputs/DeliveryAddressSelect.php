<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\Inputs;

use App\Models\Client;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;

class DeliveryAddressSelect
{
    public static function make(): Select
    {
        return Select::make('delivery_client_address_id')
            ->label('Delivery address')
            ->relationship(
                name: 'clientDeliveryAddress',
                titleAttribute: 'name',
                modifyQueryUsing: function (Builder $query, ?Client $record): Builder {
                    if (! $record) {
                        return $query->whereNull('id');
                    }

                    return $query->where('client_id', $record->getKey());
                },
            );
    }
}
