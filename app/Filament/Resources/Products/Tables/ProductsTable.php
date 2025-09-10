<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ToggleColumn;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ToggleColumn::make('active'),
                TextColumn::make('reference_code')
                    ->searchable(),
                TextColumn::make('ean')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),


                TextColumn::make('brand.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('productCategory.name')
                    ->numeric()
                    ->sortable(),
                    
                TagsColumn::make('attributes')
                    ->getStateUsing(function ($record) {
                        return $record->attributes->map(function ($attribute) {
                            return "{$attribute->attributeGroup->name}: {$attribute->name}";
                        });
                    })
                    ->searchable()
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
