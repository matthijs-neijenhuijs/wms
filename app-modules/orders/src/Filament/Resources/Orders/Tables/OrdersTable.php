<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Modules\Orders\Filament\Resources\Orders\Columns\ClientEmailColumn;
use Modules\Orders\Filament\Resources\Orders\Columns\GeneratedCustomOrderIdColumn;
use Modules\Orders\Filament\Resources\Orders\Columns\OrderStatusColumn;
use Modules\Orders\Filament\Resources\Orders\Columns\TotalPriceColumn;
use Modules\Orders\Filament\Resources\Orders\Columns\TotalQuantityColumn;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                GeneratedCustomOrderIdColumn::make(),
                OrderStatusColumn::make(),
                TotalQuantityColumn::make(),
                TotalPriceColumn::make(),
                ClientEmailColumn::make(),

                //
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->label(fn ($record) => $record->orderStatus?->canEditOrder() ? __('Edit') : __('View'))
                    ->icon('heroicon-m-eye'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
