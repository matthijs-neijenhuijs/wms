<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\OrderStatuses\Inputs;

use Closure;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;

class CompletedToggle
{
    public static function make(): Toggle
    {
        return Toggle::make('completed')
            ->label(__('Is Completed'))
            ->default(false)
            ->rules([
                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                    if ($value && ($get('generate_picklist') || $get('reserve_stock') || $get('reduce_stock'))) {
                        $fail(__('Completed cannot be combined with Generate Picklist, Reserve Stock, or Reduce Stock.'));
                    }
                },
            ]);
    }
}
