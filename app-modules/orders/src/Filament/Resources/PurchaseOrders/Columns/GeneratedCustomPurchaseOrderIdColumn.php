<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Columns;

use Filament\Tables\Columns\TextColumn;

class GeneratedCustomPurchaseOrderIdColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('generated_custom_purchase_order_id')
            ->label(__('ID'))
            ->searchable()
            ->sortable();
    }
}
