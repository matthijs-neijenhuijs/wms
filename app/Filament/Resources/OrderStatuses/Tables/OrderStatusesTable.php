<?php

namespace App\Filament\Resources\OrderStatuses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderStatusesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                ColorColumn::make('color'),

                IconColumn::make('order_is_expected')
                    ->label('Expected')
                    ->boolean()
                    ->icon(fn ($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle),

                IconColumn::make('order_is_concept')
                    ->label('Concept')
                    ->boolean()
                    ->icon(fn ($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle),

                IconColumn::make('order_is_confirmed')
                    ->label('Confirmed')
                    ->boolean()
                    ->icon(fn ($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle),

                IconColumn::make('order_is_shipped')
                    ->label('Shipped')
                    ->boolean()
                    ->icon(fn ($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle),

                IconColumn::make('order_is_delivered')
                    ->label('Delivered')
                    ->boolean()
                    ->icon(fn ($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle),

                IconColumn::make('order_is_cancelled')
                    ->label('Cancelled')
                    ->boolean()
                    ->icon(fn ($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
