<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\Inputs;

use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Modules\Clients\Models\Client;

class BillAddressSelect
{
    public static function make(): Select
    {
        return Select::make('bill_client_address_id')
            ->label('Bill address')
            ->relationship(
                name: 'clientBillAddress',
                titleAttribute: 'name',
                modifyQueryUsing: function (Builder $query, ?Client $record): Builder {
                    if (! $record) {
                        return $query->whereNull('id');
                    }

                    return $query->whereRaw('client_id = ?', [$record->getKey()]);
                },
            );
    }
}
