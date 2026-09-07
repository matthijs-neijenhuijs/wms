<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Columns;

use Filament\Tables\Columns\TextColumn;

class TotalProductsColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('products_count')
            ->label(__('Total Products'))
            ->sortable();
    }
}
