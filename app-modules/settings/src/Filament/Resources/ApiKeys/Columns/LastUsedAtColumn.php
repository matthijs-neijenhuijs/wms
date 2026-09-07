<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\ApiKeys\Columns;

use Filament\Tables\Columns\TextColumn;

class LastUsedAtColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('last_used_at')
            ->dateTime()
            ->sortable();
    }
}
