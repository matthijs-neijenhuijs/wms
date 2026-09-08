<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\Columns;

use Filament\Tables\Columns\TextColumn;
use Modules\Clients\Models\Client;

class BillAddressColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('bill_address')
            ->label('Bill address')
            ->state(function (Client $record): string {
                $address = $record->clientBillAddress;

                if (! $address) {
                    return 'Not set';
                }

                return $address->name ?? 'Not set';
            })
            ->badge()
            ->description(function (Client $record): ?string {
                $address = $record->clientBillAddress;

                if (! $address) {
                    return null;
                }

                return trim(sprintf('%s • %s', $address->address, $address->city), " \t\n\r\0\x0B•");
            });
    }
}
