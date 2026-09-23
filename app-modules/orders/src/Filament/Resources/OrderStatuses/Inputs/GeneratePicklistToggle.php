<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\OrderStatuses\Inputs;

use Closure;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;

class GeneratePicklistToggle
{
    public static function make(): Toggle
    {
        return Toggle::make('generate_picklist')
            ->label(__('Generate Picklist'))
            ->default(false)
            ->rules([
                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                    if ($value && ! $get('reserve_stock')) {
                        $fail(__('Generate Picklist requires Reserve Stock to also be enabled.'));
                    }
                },
            ]);
    }
}
