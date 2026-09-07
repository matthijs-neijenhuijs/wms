<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\Warehouses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Modules\Settings\Filament\Resources\Warehouses\Columns\CreatedAtColumn;
use Modules\Settings\Filament\Resources\Warehouses\Columns\CurrencyColumn;
use Modules\Settings\Filament\Resources\Warehouses\Columns\DomainColumn;
use Modules\Settings\Filament\Resources\Warehouses\Columns\NameColumn;
use Modules\Settings\Filament\Resources\Warehouses\Columns\UpdatedAtColumn;

class WarehousesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                NameColumn::make(),
                CurrencyColumn::make(),
                DomainColumn::make(),
                CreatedAtColumn::make(),
                UpdatedAtColumn::make(),
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
