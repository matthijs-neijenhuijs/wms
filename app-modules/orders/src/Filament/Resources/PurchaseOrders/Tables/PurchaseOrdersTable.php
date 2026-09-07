<?php

declare(strict_types=1);

namespace Modules\Orders\Filament\Resources\PurchaseOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Modules\Orders\Filament\Resources\PurchaseOrders\Columns\ExpectedDeliveryDateColumn;
use Modules\Orders\Filament\Resources\PurchaseOrders\Columns\GeneratedCustomPurchaseOrderIdColumn;
use Modules\Orders\Filament\Resources\PurchaseOrders\Columns\TotalProductsColumn;
use Modules\Orders\Filament\Resources\PurchaseOrders\Columns\TotalScannedColumn;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                GeneratedCustomPurchaseOrderIdColumn::make(),
                TotalProductsColumn::make(),
                TotalScannedColumn::make(),
                ExpectedDeliveryDateColumn::make(),
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
