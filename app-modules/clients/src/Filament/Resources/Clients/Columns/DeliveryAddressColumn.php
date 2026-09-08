<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\Columns;

use App\Models\Client;
use Filament\Tables\Columns\TextColumn;

class DeliveryAddressColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('delivery_address')
            ->label('Delivery address')
            ->state(function (Client $record): string {
                $address = $record->clientDeliveryAddress;

                if (! $address) {
                    return 'Not set';
                }

                return $address->name ?? 'Not set';
            })
            ->badge()
            ->description(function (Client $record): ?string {
                $address = $record->clientDeliveryAddress;

                if (! $address) {
                    return null;
                }

                return trim(sprintf('%s • %s', $address->address, $address->city), " \t\n\r\0\x0B•");
            });
    }
}
