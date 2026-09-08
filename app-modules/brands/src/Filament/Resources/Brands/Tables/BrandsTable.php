<?php

declare(strict_types=1);

namespace Modules\Brands\Filament\Resources\Brands\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Modules\Brands\Filament\Resources\Brands\Columns\ActiveColumn;
use Modules\Brands\Filament\Resources\Brands\Columns\NameColumn;
use Modules\Brands\Filament\Resources\Brands\Columns\ReferenceCodeColumn;

class BrandsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ActiveColumn::make(),
                ReferenceCodeColumn::make(),
                NameColumn::make(),
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
