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

                IconColumn::make('generate_picklist')
                    ->label('Generate Picklist')
                    ->boolean()
                    ->icon(fn ($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle),
                IconColumn::make('reserve_stock')
                    ->label('Reserve Stock')
                    ->boolean()
                    ->icon(fn ($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle),

                IconColumn::make('reduce_stock')
                    ->label('Reduce Stock')
                    ->boolean()
                    ->icon(fn ($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle),

                IconColumn::make('concepted')
                    ->label('Concept')
                    ->boolean()
                    ->icon(fn ($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle),

                IconColumn::make('completed')
                    ->label('Completed')
                    ->boolean()
                    ->icon(fn ($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle),

                IconColumn::make('on_hold')
                    ->label('On Hold')
                    ->boolean()
                    ->icon(fn ($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle),

                IconColumn::make('delivered')
                    ->label('Delivered')
                    ->boolean()
                    ->icon(fn ($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle),

                IconColumn::make('cancelled')
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
