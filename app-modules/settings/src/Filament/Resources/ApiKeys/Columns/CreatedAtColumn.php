<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\ApiKeys\Columns;

use Filament\Tables\Columns\TextColumn;

class CreatedAtColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('created_at')
            ->dateTime()
            ->sortable();
    }
}
