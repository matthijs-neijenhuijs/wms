<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\OrderStatuses\Columns;

use Filament\Tables\Columns\ColorColumn;

class StatusColorColumn
{
    public static function make(): ColorColumn
    {
        return ColorColumn::make('color');
    }
}
