<?php

declare(strict_types=1);

namespace Modules\Users\Filament\Resources\Users\Columns;

use Filament\Tables\Columns\TextColumn;

class SubdomainColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('subdomain.name')
            ->label('Subdomain')
            ->searchable()
            ->sortable();
    }
}
