<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Entries;

use Filament\Infolists\Components\TextEntry;

class CreatedAtEntry
{
    public static function make(): TextEntry
    {
        return TextEntry::make('created_at')
            ->dateTime();
    }
}
