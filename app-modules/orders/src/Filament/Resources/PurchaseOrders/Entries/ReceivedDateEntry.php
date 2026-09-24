<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Entries;

use Filament\Infolists\Components\TextEntry;

class ReceivedDateEntry
{
    public static function make(): TextEntry
    {
        return TextEntry::make('received_date')
            ->label(__('Received Date'))
            ->date()
            ->placeholder('—');
    }
}
