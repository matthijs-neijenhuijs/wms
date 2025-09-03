<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('active')
                    ->boolean(),
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
                    ->sortable()
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
