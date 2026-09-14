<?php

declare(strict_types=1);

namespace Modules\Picklists\Filament\Resources\Picklists\Columns;

use Filament\Tables\Columns\TextColumn;

class ProductsCountColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('products_count')
            ->label('total products')
            ->sortable();
    }
}
