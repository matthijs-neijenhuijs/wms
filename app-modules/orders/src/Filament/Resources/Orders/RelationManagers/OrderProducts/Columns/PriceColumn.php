<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Columns;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;

class PriceColumn
{
    public static function make(RelationManager $relationManager): TextColumn
    {
        return TextColumn::make('price')
            ->money(fn () => $relationManager->getOwnerRecord()->warehouse->currency ?? 'EUR');
    }
}
