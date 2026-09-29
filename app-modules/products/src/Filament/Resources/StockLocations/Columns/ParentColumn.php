<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\StockLocations\Columns;

use Filament\Tables\Columns\TextColumn;

class ParentColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('parent.name')
            ->label('Parent location')
            ->placeholder('—');
    }
}
