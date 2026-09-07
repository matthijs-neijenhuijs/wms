<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\Orders\Columns;

use Filament\Tables\Columns\TextColumn;

class ClientEmailColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('client.email')
            ->numeric()
            ->sortable();
    }
}
