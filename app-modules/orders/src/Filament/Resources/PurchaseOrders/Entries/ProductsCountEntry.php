<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Entries;

use Filament\Infolists\Components\TextEntry;
use Modules\Orders\Models\PurchaseOrder;

class ProductsCountEntry
{
    public static function make(): TextEntry
    {
        return TextEntry::make('products_count')
            ->label(__('Products'))
            ->state(fn (PurchaseOrder $record): int => $record->products->count());
    }
}
