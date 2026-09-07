<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\VatRates\Columns;

use Filament\Tables\Columns\TextColumn;

class RateColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('rate');
    }
}
