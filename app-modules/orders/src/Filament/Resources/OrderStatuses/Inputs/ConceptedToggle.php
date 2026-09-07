<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\OrderStatuses\Inputs;

use Filament\Forms\Components\Toggle;

class ConceptedToggle
{
    public static function make(): Toggle
    {
        return Toggle::make('concepted')
            ->label(__('Is Concepted'))
            ->default(false);
    }
}
