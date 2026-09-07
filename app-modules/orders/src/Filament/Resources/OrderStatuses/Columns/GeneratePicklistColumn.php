<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\OrderStatuses\Columns;

use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;

class GeneratePicklistColumn
{
    public static function make(): IconColumn
    {
        return IconColumn::make('generate_picklist')
            ->label(__('Generate Picklist'))
            ->boolean()
            ->icon(fn ($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle);
    }
}
