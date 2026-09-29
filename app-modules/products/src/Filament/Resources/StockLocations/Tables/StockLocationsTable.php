<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\StockLocations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Modules\Products\Filament\Resources\StockLocations\Columns\NameColumn;
use Modules\Products\Filament\Resources\StockLocations\Columns\ParentColumn;
use Modules\Products\Filament\Resources\StockLocations\Columns\ProductsCountColumn;

class StockLocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                NameColumn::make(),
                ParentColumn::make(),
                ProductsCountColumn::make(),
            ])
            ->filters([
                //
            ])
            ->reorderable('rank')
            ->defaultSort('rank')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
