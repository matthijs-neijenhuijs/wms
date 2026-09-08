<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Inputs;

use Filament\Forms\Components\Select;

class VatRateSelect
{
    public static function make(): Select
    {
        return Select::make('vat_rate_id')
            ->relationship(name: 'vatRate', titleAttribute: 'name');
    }
}
