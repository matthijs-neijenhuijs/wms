<?php

namespace App\Filament\Resources\PurchaseOrders\Tables;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\PurchaseOrder;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('generated_custom_purchase_order_id')
                    ->label('id')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('order.generated_custom_order_id')
                    ->label('order id')
                    ->url(fn (PurchaseOrder $record): ?string => $record->order
                        ? OrderResource::getUrl('edit', ['record' => $record->order])
                        : null)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('products_count')
                    ->label('total products')
                    ->sortable(),
                TextColumn::make('scanned_products_count')
                    ->label('total scanned')
                    ->sortable(),
                TextColumn::make('expected_delivery_date')
                    ->date()
                    ->sortable(),
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
