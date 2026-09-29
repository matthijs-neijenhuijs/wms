<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\StockLocations\Columns;

use Filament\Tables\Columns\TextColumn;

class ProductsCountColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('products_count')
            ->label('Products')
            ->counts('products')
            ->numeric();
    }
}
