<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Columns;

use Filament\Tables\Columns\TextColumn;

class TotalScannedColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('scanned_products_count')
            ->label(__('Total Scanned'))
            ->sortable();
    }
}
