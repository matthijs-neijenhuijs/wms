<?php

declare(strict_types=1);

namespace Modules\Picklists\Filament\Resources\Picklists\Columns;

use App\Models\Picklist;
use Filament\Tables\Columns\TextColumn;
use Modules\Orders\Filament\Resources\Orders\OrderResource;

class OrderGeneratedCustomOrderIdColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('order.generated_custom_order_id')
            ->label('order id')
            ->url(fn (Picklist $record): ?string => $record->order
                ? OrderResource::getUrl('edit', ['record' => $record->order])
                : null)
            ->searchable()
            ->sortable();
    }
}
