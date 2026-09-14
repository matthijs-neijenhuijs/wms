<?php

declare(strict_types=1);

namespace Modules\Brands\Filament\Resources\Brands\Columns;

use Filament\Tables\Columns\TextColumn;

class ReferenceCodeColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('reference_code');
    }
}
