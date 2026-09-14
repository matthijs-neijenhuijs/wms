<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Inputs;

use Filament\Forms\Components\Select;

class BrandSelect
{
    public static function make(): Select
    {
        return Select::make('brand_id')
            ->relationship(name: 'brand', titleAttribute: 'name');
    }
}
