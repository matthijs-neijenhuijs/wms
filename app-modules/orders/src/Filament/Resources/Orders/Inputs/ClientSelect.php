<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\Orders\Inputs;

use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Modules\Clients\Models\Client;

class ClientSelect
{
    public static function make(): Select
    {
        return Select::make('client_id')
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
            ->searchable(['company', 'email']);
    }
}
