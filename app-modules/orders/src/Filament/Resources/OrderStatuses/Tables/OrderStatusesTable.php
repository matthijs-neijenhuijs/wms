<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\OrderStatuses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Modules\Orders\Filament\Resources\OrderStatuses\Columns\CancelledColumn;
use Modules\Orders\Filament\Resources\OrderStatuses\Columns\CompletedColumn;
use Modules\Orders\Filament\Resources\OrderStatuses\Columns\ConceptColumn;
use Modules\Orders\Filament\Resources\OrderStatuses\Columns\CreatedAtColumn;
use Modules\Orders\Filament\Resources\OrderStatuses\Columns\DeliveredColumn;
use Modules\Orders\Filament\Resources\OrderStatuses\Columns\GeneratePicklistColumn;
use Modules\Orders\Filament\Resources\OrderStatuses\Columns\NameColumn;
use Modules\Orders\Filament\Resources\OrderStatuses\Columns\OnHoldColumn;
use Modules\Orders\Filament\Resources\OrderStatuses\Columns\ReduceStockColumn;
use Modules\Orders\Filament\Resources\OrderStatuses\Columns\ReserveStockColumn;
use Modules\Orders\Filament\Resources\OrderStatuses\Columns\StatusColorColumn;
use Modules\Orders\Filament\Resources\OrderStatuses\Columns\UpdatedAtColumn;

class OrderStatusesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                NameColumn::make(),
                StatusColorColumn::make(),
                GeneratePicklistColumn::make(),
                ReserveStockColumn::make(),
                ReduceStockColumn::make(),
                ConceptColumn::make(),
                CompletedColumn::make(),
                OnHoldColumn::make(),
                DeliveredColumn::make(),
                CancelledColumn::make(),
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
