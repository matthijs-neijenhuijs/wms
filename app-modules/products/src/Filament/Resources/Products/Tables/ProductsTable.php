<?php

declare(strict_types=1);

namespace Modules\Products\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Products\Filament\Resources\Products\Columns\ActiveColumn;
use Modules\Products\Filament\Resources\Products\Columns\NameWithAttributesColumn;
use Modules\Products\Filament\Resources\Products\Columns\ReferenceCodeColumn;
use Modules\Products\Filament\Resources\Products\Columns\StockSummaryColumn;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['brand', 'stockProduct', 'attributes.attributeGroup']))
            ->columns([
                ActiveColumn::make(),
                ReferenceCodeColumn::make(),
                NameWithAttributesColumn::make(),
                StockSummaryColumn::make(),

            ])
            ->filters([
                //
            ])
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
