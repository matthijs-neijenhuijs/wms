<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\StockLocations\Inputs;

use Closure;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Modules\Products\Models\StockLocation;

class ParentSelect
{
    public static function make(): Select
    {
        return Select::make('parent_id')
            ->relationship(name: 'parent', titleAttribute: 'name', ignoreRecord: true)
            ->searchable()
            ->preload()
            ->label('Parent location')
            ->rules([
                fn (?Model $record): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                    if (blank($value) || ! $record instanceof StockLocation) {
                        return;
                    }

                    if ((int) $value === $record->getKey()) {
                        $fail('A location cannot be its own parent.');

                        return;
                    }

                    $cursor = StockLocation::find($value);

                    while ($cursor?->parent_id) {
                        if ((int) $cursor->parent_id === $record->getKey()) {
                            $fail('A location cannot be nested under one of its own sub-locations.');

                            return;
                        }

                        $cursor = $cursor->parent;
                    }
                },
            ]);
    }
}
