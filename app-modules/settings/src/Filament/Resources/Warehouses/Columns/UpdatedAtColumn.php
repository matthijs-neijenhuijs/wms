<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\Warehouses\Columns;

use Filament\Tables\Columns\TextColumn;

class UpdatedAtColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('updated_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
