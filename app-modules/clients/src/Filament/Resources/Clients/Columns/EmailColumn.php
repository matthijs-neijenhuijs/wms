<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\Columns;

use Filament\Tables\Columns\TextColumn;

class EmailColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('email');
    }
}
