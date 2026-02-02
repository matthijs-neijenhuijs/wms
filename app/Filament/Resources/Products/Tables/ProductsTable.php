<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

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

                TagsColumn::make('attributes')
                    ->getStateUsing(function ($record) {
                        return $record->attributes->map(function ($attribute) {
                            return "{$attribute->attributeGroup->name}: {$attribute->name}";
                        });
                    })
                    ->searchable(),
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
