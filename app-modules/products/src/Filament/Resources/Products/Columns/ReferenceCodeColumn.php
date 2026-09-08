<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Columns;

use Filament\Tables\Columns\TextColumn;

class ReferenceCodeColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('reference_code');
    }
}
