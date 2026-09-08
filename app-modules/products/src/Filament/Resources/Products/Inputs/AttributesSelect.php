<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Inputs;

use Filament\Forms\Components\Select;

class AttributesSelect
{
    public static function make(): Select
    {
        return Select::make('attributes')
            ->multiple()
            ->relationship('attributes', titleAttribute: 'name')
            ->preload()
            ->searchable()
            ->getOptionLabelFromRecordUsing(fn ($record): string => sprintf('%s: %s', optional($record->attributeGroup)->name ?? 'Ungrouped', $record->name))
            ->columnSpanFull();
    }
}
