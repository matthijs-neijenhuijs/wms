<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\OrderStatuses\Inputs;

use Filament\Forms\Components\Toggle;

class GeneratePicklistToggle
{
    public static function make(): Toggle
    {
        return Toggle::make('generate_picklist')
            ->label(__('Generate Picklist'))
            ->default(false);
    }
}
