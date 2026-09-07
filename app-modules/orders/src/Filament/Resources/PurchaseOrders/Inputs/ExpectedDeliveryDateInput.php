<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Inputs;

use Filament\Forms\Components\DatePicker;

class ExpectedDeliveryDateInput
{
    public static function make(): DatePicker
    {
        return DatePicker::make('expected_delivery_date')
            ->required();
    }
}
