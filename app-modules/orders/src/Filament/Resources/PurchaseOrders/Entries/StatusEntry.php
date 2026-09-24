<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Entries;

use Filament\Infolists\Components\TextEntry;

class StatusEntry
{
    public static function make(): TextEntry
    {
        return TextEntry::make('status')
            ->label(__('Status'))
            ->badge();
    }
}
