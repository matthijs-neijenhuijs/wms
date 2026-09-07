<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\Orders\Columns;

use Filament\Tables\Columns\TextColumn;

class GeneratedCustomOrderIdColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('generated_custom_order_id')
            ->label(__('ID'))
            ->searchable();
    }
}
