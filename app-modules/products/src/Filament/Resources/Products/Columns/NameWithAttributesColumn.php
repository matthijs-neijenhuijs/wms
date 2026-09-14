<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Columns;

use Filament\Tables\Columns\TextColumn;

class NameWithAttributesColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('name')
            ->html()
            ->formatStateUsing(function (string $state, $record): string {
                $brandName = $record->brand?->name;
                $nameWithBrand = $brandName ? "{$brandName} - {$state}" : $state;

                $attributeBadges = $record->attributes
                    ->map(function ($attribute): string {
                        $groupName = optional($attribute->attributeGroup)->name ?? 'Ungrouped';
                        $label = e("{$groupName}: {$attribute->name}");

                        return '<span class="fi-color fi-color-primary fi-text-color-700 dark:fi-text-color-400 fi-badge fi-size-sm inline-flex w-fit whitespace-nowrap">'.$label.'</span>';
                    })
                    ->implode('');

                if ($attributeBadges === '') {
                    return e($nameWithBrand);
                }

                return '<div>'.e($nameWithBrand).'</div><div class="mt-1 flex flex-col items-start gap-1">'.$attributeBadges.'</div>';
            });
    }
}
