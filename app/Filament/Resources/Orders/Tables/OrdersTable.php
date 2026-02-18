<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('generated_custom_order_id')->label('id')
                    ->searchable(),

                TextColumn::make('orderStatus.name')
                    ->label('Status')
                    ->html()
                    ->formatStateUsing(function ($record) {
                        $color = $record->orderStatus?->color ?? '#6b7280';
                        $name = $record->orderStatus?->name ?? 'Unknown';

                        return "<span class='fi-badge inline-flex items-center justify-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset' style='background-color: {$color}; color: white; border-color: {$color};'>{$name}</span>";
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('products_sum_quantity')
                    ->label('Total Quantity')
                    ->sum('products', 'quantity')
                    ->sortable(),

                TextColumn::make('total_price')
                    ->label('Total Price')
                    ->state(function ($record) {
                        return $record->products->sum(function ($product) {
                            return ($product->quantity ?? 0) * ($product->price ?? 0);
                        });
                    })
                    ->money(fn ($record) => $record->warehouse?->currency ?? 'EUR')
                    ->sortable(),

                TextColumn::make('client.email')
                    ->numeric()
                    ->sortable(),

                //
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->label(fn ($record) => $record->orderStatus?->canEditOrder() ? 'Edit' : 'View')
                    ->icon('heroicon-m-eye'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
