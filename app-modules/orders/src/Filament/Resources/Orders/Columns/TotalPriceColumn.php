<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\Orders\Columns;

use Filament\Tables\Columns\TextColumn;
use Modules\Orders\Models\Order;

class TotalPriceColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('total_price')
            ->label(__('Total Price'))
            ->state(function (Order $record): int|float {
                return $record->products->sum(function ($product) {
                    return ($product->quantity ?? 0) * ($product->price ?? 0);
                });
            })
            ->money(fn (Order $record) => $record->warehouse->currency ?? 'EUR')
            ->sortable();
    }
}
