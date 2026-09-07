<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\OrderStatuses\Columns;

use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;

class DeliveredColumn
{
    public static function make(): IconColumn
    {
        return IconColumn::make('delivered')
            ->label(__('Delivered'))
            ->boolean()
            ->icon(fn ($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle);
    }
}
