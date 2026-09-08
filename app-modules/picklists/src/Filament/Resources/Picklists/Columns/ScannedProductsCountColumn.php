<?php

declare(strict_types=1);

namespace Modules\Picklists\Filament\Resources\Picklists\Columns;

use Filament\Tables\Columns\TextColumn;

class ScannedProductsCountColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('scanned_products_count')
            ->label('total scanned')
            ->sortable();
    }
}
