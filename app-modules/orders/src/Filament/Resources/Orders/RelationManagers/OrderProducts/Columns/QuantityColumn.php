<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Columns;

use Filament\Tables\Columns\TextColumn;

class QuantityColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('quantity');
    }
}
