<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\OrderStatuses\Inputs;

use Filament\Forms\Components\Toggle;

class CompletedToggle
{
    public static function make(): Toggle
    {
        return Toggle::make('completed')
            ->label(__('Is Completed'))
            ->default(false);
    }
}
