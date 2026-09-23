<?php

declare(strict_types=1);

namespace Modules\Clients\Filament\Resources\Clients\RelationManagers\Addresses\Columns;

use Filament\Tables\Columns\TextColumn;

class AddressColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('address')
            ->searchable();
    }
}
