<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\Orders\Columns;

use Filament\Tables\Columns\TextColumn;
use Modules\Orders\Models\Order;

class OrderStatusColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('orderStatus.name')
            ->label(__('Status'))
            ->html()
            ->formatStateUsing(function (Order $record): string {
                $color = $record->orderStatus->color ?? '#6b7280';
                $name = $record->orderStatus->name ?? 'Unknown';

                return "<span class='fi-badge inline-flex items-center justify-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset' style='background-color: {$color}; color: white; border-color: {$color};'>{$name}</span>";
            })
            ->searchable()
            ->sortable();
    }
}
