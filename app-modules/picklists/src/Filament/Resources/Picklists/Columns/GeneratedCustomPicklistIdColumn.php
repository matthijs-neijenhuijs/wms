<?php

declare(strict_types=1);

namespace Modules\Picklists\Filament\Resources\Picklists\Columns;

use Filament\Tables\Columns\TextColumn;

class GeneratedCustomPicklistIdColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('generated_custom_picklist_id')
            ->label('id')
            ->searchable()
            ->sortable();
    }
}
