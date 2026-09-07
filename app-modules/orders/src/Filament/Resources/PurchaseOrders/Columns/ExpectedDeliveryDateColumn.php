<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Columns;

use Filament\Tables\Columns\TextColumn;

class ExpectedDeliveryDateColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('expected_delivery_date')
            ->date()
            ->sortable();
    }
}
