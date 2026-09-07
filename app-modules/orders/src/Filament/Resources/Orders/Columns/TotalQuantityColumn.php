<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\Orders\Columns;

use Filament\Tables\Columns\TextColumn;

class TotalQuantityColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('products_sum_quantity')
            ->label(__('Total Quantity'))
            ->sum('products', 'quantity')
            ->sortable();
    }
}
