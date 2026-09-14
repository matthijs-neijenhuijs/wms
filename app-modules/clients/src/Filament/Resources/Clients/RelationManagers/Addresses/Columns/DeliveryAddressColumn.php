<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Columns;

use Filament\Tables\Columns\IconColumn;

class DeliveryAddressColumn
{
    public static function make(): IconColumn
    {
        return IconColumn::make('isDeliveryAddress')
            ->label('Delivery address')
            ->boolean();
    }
}
