<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Columns;

use Filament\Tables\Columns\ToggleColumn;

class ActiveColumn
{
    public static function make(): ToggleColumn
    {
        return ToggleColumn::make('active');
    }
}
