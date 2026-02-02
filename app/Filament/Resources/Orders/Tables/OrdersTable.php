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

                TextColumn::make('status')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('products_sum_quantity')
                    ->label('Total Quantity')
                    ->sum('products', 'quantity')
                    ->sortable(),

                TextColumn::make('total_price')
                    ->label('Total Price')
                    ->money('EUR')
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
                EditAction::make()->icon('heroicon-m-eye'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
