<?php

declare(strict_types=1);

namespace Modules\Brands\Filament\Resources\Brands\Columns;

use Filament\Tables\Columns\ToggleColumn;

class ActiveColumn
{
    public static function make(): ToggleColumn
    {
        return ToggleColumn::make('active');
    }
}
