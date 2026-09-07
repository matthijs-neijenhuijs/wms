<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\AttributeGroups\Columns;

use Filament\Tables\Columns\TextColumn;

class NameColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('name');
    }
}
