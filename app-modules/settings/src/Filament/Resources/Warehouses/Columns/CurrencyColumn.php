<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\Warehouses\Columns;

use Filament\Tables\Columns\TextColumn;

class CurrencyColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('currency')
            ->searchable()
            ->sortable();
    }
}
