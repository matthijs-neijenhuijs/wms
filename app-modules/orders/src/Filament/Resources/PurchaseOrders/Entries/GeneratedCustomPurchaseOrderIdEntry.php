<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Entries;

use Filament\Infolists\Components\TextEntry;

class GeneratedCustomPurchaseOrderIdEntry
{
    public static function make(): TextEntry
    {
        return TextEntry::make('generated_custom_purchase_order_id')
            ->label(__('ID'));
    }
}
