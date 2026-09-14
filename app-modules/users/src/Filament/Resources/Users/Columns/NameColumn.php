<?php

declare(strict_types=1);

namespace Modules\Users\Filament\Resources\Users\Columns;

use Filament\Tables\Columns\TextColumn;

class NameColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('name')
            ->searchable()
            ->sortable();
    }
}
