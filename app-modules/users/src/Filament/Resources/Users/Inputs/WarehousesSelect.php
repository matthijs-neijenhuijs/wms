<?php

declare(strict_types=1);

namespace Modules\Users\Filament\Resources\Users\Inputs;

use Filament\Forms\Components\Select;

class WarehousesSelect
{
    public static function make(): Select
    {
        return Select::make('warehouses')
            ->relationship(
                name: 'warehouses',
                titleAttribute: 'name',
                modifyQueryUsing: function ($query) {
                    if (app()->has('current_subdomain')) {
                        return $query->where('subdomain_id', app('current_subdomain')->id);
                    }

                    return $query;
                }
            )
            ->multiple()
            ->searchable()
            ->preload()
            ->label('Warehouses');
    }
}
