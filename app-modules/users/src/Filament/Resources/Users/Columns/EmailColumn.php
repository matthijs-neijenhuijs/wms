<?php

declare(strict_types=1);

namespace Modules\Users\Filament\Resources\Users\Columns;

use Filament\Tables\Columns\TextColumn;

class EmailColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('email')
            ->searchable()
            ->sortable();
    }
}
