<?php

declare(strict_types=1);

namespace Modules\Users\Filament\Resources\Users\Columns;

use Filament\Tables\Columns\TextColumn;

class CreatedAtColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('created_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
