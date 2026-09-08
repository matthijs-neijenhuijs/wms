<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\Warehouses\Inputs;

use App\Models\Warehouse;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;

class CompletedPicklistStatusSelect
{
    public static function make(): Select
    {
        return Select::make('order_statuses_id_completed_picklist')
            ->label(__('Completed Picklist Status'))
            ->relationship(
                name: 'completedPicklistOrderStatus',
                titleAttribute: 'name',
                modifyQueryUsing: function (Builder $query, ?Warehouse $record): Builder {
                    if (! $record) {
                        return $query->whereNull('id');
                    }

                    return $query
                        ->whereRaw('warehouse_id = ?', [$record->getKey()])
                        ->orderBy('name');
                },
            )
            ->nullable()
            ->placeholder(__('None'))
            ->searchable()
            ->preload();
    }
}
