<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Inputs;

use Filament\Forms\Components\DatePicker;

class ReceivedDateInput
{
    public static function make(): DatePicker
    {
        return DatePicker::make('received_date');
    }
}
