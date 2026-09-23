<?php

declare(strict_types=1);

namespace Modules\Picklists\Filament\Resources\Picklists\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Modules\Picklists\Filament\Resources\Picklists\Columns\GeneratedCustomPicklistIdColumn;
use Modules\Picklists\Filament\Resources\Picklists\Columns\OrderGeneratedCustomOrderIdColumn;
use Modules\Picklists\Filament\Resources\Picklists\Columns\ProductsCountColumn;
use Modules\Picklists\Filament\Resources\Picklists\Columns\ScannedProductsCountColumn;

class PicklistsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                GeneratedCustomPicklistIdColumn::make(),
                OrderGeneratedCustomOrderIdColumn::make(),
                ProductsCountColumn::make(),
                ScannedProductsCountColumn::make(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
