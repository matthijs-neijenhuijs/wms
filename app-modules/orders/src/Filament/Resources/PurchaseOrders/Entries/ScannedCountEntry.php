<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Entries;

use Filament\Infolists\Components\TextEntry;
use Modules\Orders\Models\PurchaseOrder;

class ScannedCountEntry
{
    public static function make(): TextEntry
    {
        return TextEntry::make('scanned_count')
            ->label(__('Scanned'))
            ->state(fn (PurchaseOrder $record): string => $record->products->where('scanned', true)->count().' / '.$record->products->count());
    }
}
