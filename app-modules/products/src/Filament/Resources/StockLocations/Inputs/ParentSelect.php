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

                    if (self::wouldCreateCycle($record, (int) $value)) {
                        $fail(__('A location cannot be its own parent or nested under one of its own sub-locations.'));
                    }
                },
            ]);
    }

    private static function wouldCreateCycle(StockLocation $record, int $parentId): bool
    {
        if ($parentId === $record->getKey()) {
            return true;
        }

        $cursor = StockLocation::query()->find($parentId);

        while ($cursor?->parent_id) {
            if ((int) $cursor->parent_id === $record->getKey()) {
                return true;
            }

            $cursor = $cursor->parent;
        }

        return false;
    }
}
